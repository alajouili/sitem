<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Exceptions\ImportException;
use ZipArchive;

/**
 * Minimal XLSX reader. No PhpSpreadsheet/composer dependency — an .xlsx
 * file is just a zip of XML parts, so this reads directly:
 *   - xl/sharedStrings.xml -> string table
 *   - xl/workbook.xml + xl/_rels/workbook.xml.rels -> sheet name -> path
 *   - xl/worksheets/sheetN.xml -> row/cell data
 *   - xl/media/* -> embedded images (used by ImageExtractionService)
 *
 * Only reads what ExcelImportService needs (values + media); does not
 * support formulas, styles, or writing.
 */
final class ExcelHelper
{
    /**
     * @return array<int, array{name:string, path:string}> sheet name => internal path, in workbook order
     */
    public static function sheetList(string $filePath): array
    {
        $zip = self::open($filePath);

        $workbookXml = self::readEntry($zip, 'xl/workbook.xml');
        $relsXml = self::readEntry($zip, 'xl/_rels/workbook.xml.rels');
        $zip->close();

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if ($workbook === false || $rels === false) {
            throw new ImportException('The workbook could not be parsed.');
        }

        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $target = (string) $rel['Target'];
            $resolved = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;
            $relMap[(string) $rel['Id']] = $resolved;
        }

        $namespaces = $workbook->getNamespaces(true);

        $sheets = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes($namespaces['r'] ?? '');
            $rId = (string) ($attrs['id'] ?? '');

            $sheets[] = [
                'name' => (string) $sheet['name'],
                'path' => $relMap[$rId] ?? null,
            ];
        }

        return array_values(array_filter($sheets, fn ($s) => $s['path'] !== null));
    }

    /**
     * Reads every row of the given sheet (by zero-based index in workbook
     * order, or by name) into an array of associative rows keyed by the
     * header row (row 1), unless $withHeader is false.
     *
     * The returned array preserves the original worksheet row number as
     * its key (e.g. 2, 3, 5 if row 4 was blank and skipped) rather than
     * reindexing from 0 — this lets callers correlate parsed data rows
     * with embedded images via extractMediaWithAnchors(), which reports
     * the same row numbers.
     *
     * @return array<int, array<string, string|null>>
     */
    public static function readRows(string $filePath, int|string $sheet = 0, bool $withHeader = true): array
    {
        $sheets = self::sheetList($filePath);

        $target = is_int($sheet)
            ? ($sheets[$sheet] ?? null)
            : self::findSheetByName($sheets, $sheet);

        if ($target === null) {
            throw new ImportException('The requested sheet was not found in the workbook.');
        }

        $zip = self::open($filePath);
        $sharedStrings = self::readSharedStrings($zip);
        $sheetXml = self::readEntry($zip, $target['path']);
        $zip->close();

        $sheetNode = simplexml_load_string($sheetXml);

        if ($sheetNode === false) {
            throw new ImportException('The worksheet could not be parsed.');
        }

        $rawRows = [];
        foreach ($sheetNode->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $col = self::columnLetters($ref);
                $type = (string) $cell['t'];

                // Cells carrying a `vm` attribute hold a rich value (e.g. an
                // Excel "image in cell") rather than real text/number data —
                // their cached <v> is just a "#VALUE!" placeholder. Treat as
                // null here; the actual image is resolved separately by
                // extractCellImages().
                $cells[$col] = isset($cell['vm']) ? null : self::cellValue($cell, $type, $sharedStrings);
            }

            $rawRows[(int) $row['r']] = $cells;
        }

        if (empty($rawRows)) {
            return [];
        }

        ksort($rawRows);

        if (!$withHeader) {
            return $rawRows;
        }

        $rowNumbers = array_keys($rawRows);
        $headerRowNumber = array_shift($rowNumbers);
        $headerRow = $rawRows[$headerRowNumber];

        // Map column letter -> header label
        $headerByColumn = [];
        foreach ($headerRow as $col => $label) {
            $label = trim((string) $label);
            if ($label !== '') {
                $headerByColumn[$col] = $label;
            }
        }

        $result = [];
        foreach ($rowNumbers as $rowNumber) {
            $rowCells = $rawRows[$rowNumber];
            $mapped = [];

            foreach ($headerByColumn as $col => $label) {
                $mapped[$label] = $rowCells[$col] ?? null;
            }

            // Skip fully-empty rows
            if (count(array_filter($mapped, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $result[$rowNumber] = $mapped;
        }

        return $result;
    }

    /**
     * Extracts every embedded image under xl/media/ in the workbook.
     *
     * @return array<int, array{name:string, contents:string, mime:string}>
     */
    public static function extractMedia(string $filePath): array
    {
        $zip = self::open($filePath);
        $media = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if ($entryName === false || !str_starts_with($entryName, 'xl/media/')) {
                continue;
            }

            $contents = $zip->getFromIndex($i);

            if ($contents === false) {
                continue;
            }

            $media[] = [
                'name'     => basename($entryName),
                'contents' => $contents,
                'mime'     => ImageHelper::mimeFromBinary($contents),
            ];
        }

        $zip->close();

        return $media;
    }

    /**
     * Extracts embedded images from the given sheet AND resolves which
     * worksheet row each image is anchored to, by following:
     *   sheetN.xml -> <drawing r:id> -> worksheets/_rels -> drawingN.xml
     *   -> <xdr:from><xdr:row> (0-based) -> +1 for the 1-based sheet row
     *   -> drawingN.xml.rels -> r:embed -> xl/media/imageX.ext
     *
     * Falls back to an empty 'row' correlation (still returns the image)
     * if a workbook has no drawing/anchors — e.g. images pasted without
     * a cell anchor, or a non-standard generator.
     *
     * @return array<int, array{row:?int, name:string, contents:string, mime:string}>
     */
    public static function extractMediaWithAnchors(string $filePath, int|string $sheet = 0): array
    {
        $sheets = self::sheetList($filePath);
        $target = is_int($sheet) ? ($sheets[$sheet] ?? null) : self::findSheetByName($sheets, $sheet);

        if ($target === null) {
            throw new ImportException('The requested sheet was not found in the workbook.');
        }

        $zip = self::open($filePath);

        $sheetDir = dirname($target['path']); // e.g. "xl/worksheets"
        $sheetBasename = basename($target['path']); // e.g. "sheet1.xml"
        $sheetRelsPath = "{$sheetDir}/_rels/{$sheetBasename}.rels";
        $sheetRelsXml = self::readEntry($zip, $sheetRelsPath, required: false);

        if ($sheetRelsXml === null) {
            $zip->close();
            // No drawing relationship at all -> try the newer "image in
            // cell" rich-value mechanism before giving up on row linkage.
            return self::resolveMediaFallback($filePath, $sheet);
        }

        $sheetRels = simplexml_load_string($sheetRelsXml);
        $drawingPath = null;

        foreach ($sheetRels->Relationship as $rel) {
            if (str_contains((string) $rel['Type'], '/drawing')) {
                $drawingPath = self::resolveTarget($sheetDir, (string) $rel['Target']);
                break;
            }
        }

        if ($drawingPath === null) {
            $zip->close();
            return self::resolveMediaFallback($filePath, $sheet);
        }

        $drawingXml = self::readEntry($zip, $drawingPath, required: false);
        $drawingDir = dirname($drawingPath);
        $drawingBasename = basename($drawingPath);
        $drawingRelsXml = self::readEntry($zip, "{$drawingDir}/_rels/{$drawingBasename}.rels", required: false);

        if ($drawingXml === null || $drawingRelsXml === null) {
            $zip->close();
            return self::resolveMediaFallback($filePath, $sheet);
        }

        // r:embed id -> media target path
        $drawingRels = simplexml_load_string($drawingRelsXml);
        $embedMap = [];
        foreach ($drawingRels->Relationship as $rel) {
            $embedMap[(string) $rel['Id']] = self::resolveTarget($drawingDir, (string) $rel['Target']);
        }

        $drawing = simplexml_load_string($drawingXml);
        $namespaces = $drawing->getNamespaces(true);
        $rNs = $namespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        $results = [];

        foreach (['oneCellAnchor', 'twoCellAnchor'] as $anchorType) {
            if (!isset($drawing->{$anchorType})) {
                continue;
            }

            foreach ($drawing->{$anchorType} as $anchor) {
                if (!isset($anchor->pic)) {
                    continue;
                }

                $row = isset($anchor->from->row) ? ((int) $anchor->from->row) + 1 : null;
                $blip = $anchor->pic->blipFill->children('http://schemas.openxmlformats.org/drawingml/2006/main')->blip;
                $rIdAttrs = $blip->attributes($rNs);
                $rId = (string) ($rIdAttrs['embed'] ?? '');

                $mediaPath = $embedMap[$rId] ?? null;
                if ($mediaPath === null) {
                    continue;
                }

                $contents = $zip->getFromName($mediaPath);
                if ($contents === false) {
                    continue;
                }

                $results[] = [
                    'row'      => $row,
                    'name'     => basename($mediaPath),
                    'contents' => $contents,
                    'mime'     => ImageHelper::mimeFromBinary($contents),
                ];
            }
        }

        $zip->close();

        return $results;
    }

    /**
     * Resolves a relationship Target against the directory of the part
     * that referenced it. Targets starting with "/" are package-root
     * relative; everything else is relative to $baseDir (handling "../").
     */
    private static function resolveTarget(string $baseDir, string $target): string
    {
        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }

        $combined = $baseDir . '/' . $target;
        $parts = [];

        foreach (explode('/', $combined) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $segment;
        }

        return implode('/', $parts);
    }

    /**
     * Called whenever no classic drawing/anchor is found for a sheet.
     * Tries the newer "image in cell" rich-value mechanism first (precise
     * row linkage); falls back to unlinked media extraction only if that
     * workbook doesn't use rich values either.
     */
    private static function resolveMediaFallback(string $filePath, int|string $sheet): array
    {
        $cellImages = self::extractCellImages($filePath, $sheet);

        if (!empty($cellImages)) {
            return $cellImages;
        }

        return array_map(fn ($m) => ['row' => null, ...$m], self::extractMedia($filePath));
    }

    /**
     * Resolves images inserted via Excel's "Insert > Pictures > Place in
     * Cell" feature (also called IMAGE()-in-cell / rich values), which is
     * an entirely different mechanism from the classic floating/anchored
     * drawings that extractMediaWithAnchors() otherwise handles. The
     * chain is:
     *
     *   sheetN.xml <c vm="k">           — cell references metadata block k (1-based)
     *   xl/metadata.xml <bk> #(k-1)     — <xlrd:rvb i="n"/> gives rich-value index n
     *   xl/richData/rdrichvalue.xml     — <rv> #n's first <v> is a rel index r
     *   xl/richData/richValueRel.xml    — <rel> #r gives an r:id
     *   .../_rels/richValueRel.xml.rels — r:id resolves to the actual media Target
     *
     * Returns [] (not an error) if the workbook has no rich-value images
     * at all — callers should treat that as "nothing found here".
     *
     * @return array<int, array{row:?int, name:string, contents:string, mime:string}>
     */
    public static function extractCellImages(string $filePath, int|string $sheet = 0): array
    {
        $sheets = self::sheetList($filePath);
        $target = is_int($sheet) ? ($sheets[$sheet] ?? null) : self::findSheetByName($sheets, $sheet);

        if ($target === null) {
            return [];
        }

        $zip = self::open($filePath);

        $metadataXml = self::readEntry($zip, 'xl/metadata.xml', required: false);
        $richValueXml = self::readEntry($zip, 'xl/richData/rdrichvalue.xml', required: false);
        $richValueRelXml = self::readEntry($zip, 'xl/richData/richValueRel.xml', required: false);
        $richValueRelRelsXml = self::readEntry($zip, 'xl/richData/_rels/richValueRel.xml.rels', required: false);
        $sheetXml = self::readEntry($zip, $target['path']);

        if ($metadataXml === null || $richValueXml === null || $richValueRelXml === null || $richValueRelRelsXml === null) {
            $zip->close();
            return [];
        }

        // 1. metadata.xml: ordered list of <bk> blocks, each optionally
        //    carrying <xlrd:rvb i="n"/> -> bkIndex (0-based) => rich-value index n
        $metadata = simplexml_load_string($metadataXml);
        $bkToRichValueIndex = [];
        if ($metadata !== false && isset($metadata->futureMetadata->bk)) {
            $namespaces = $metadata->getNamespaces(true);
            $xlrdNs = $namespaces['xlrd'] ?? 'http://schemas.microsoft.com/office/spreadsheetml/2017/richdata';

            $index = 0;
            foreach ($metadata->futureMetadata->bk as $bk) {
                $rvb = $bk->extLst->ext->children($xlrdNs)->rvb ?? null;
                if ($rvb !== null) {
                    $bkToRichValueIndex[$index] = (int) $rvb->attributes()['i'];
                }
                $index++;
            }
        }

        // 2. rdrichvalue.xml: ordered list of <rv> blocks; first <v> child
        //    is the index into richValueRel.xml's <rel> list.
        $richValueNode = simplexml_load_string($richValueXml);
        $richValueToRelIndex = [];
        if ($richValueNode !== false) {
            $index = 0;
            foreach ($richValueNode->rv as $rv) {
                $firstValue = $rv->v[0] ?? null;
                if ($firstValue !== null) {
                    $richValueToRelIndex[$index] = (int) $firstValue;
                }
                $index++;
            }
        }

        // 3. richValueRel.xml: ordered list of <rel r:id="..."/>
        $richValueRelNode = simplexml_load_string($richValueRelXml);
        $relIndexToRId = [];
        if ($richValueRelNode !== false) {
            $namespaces = $richValueRelNode->getNamespaces(true);
            $rNs = $namespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

            $index = 0;
            foreach ($richValueRelNode->rel as $rel) {
                $idAttr = $rel->attributes($rNs)['id'] ?? null;
                if ($idAttr !== null) {
                    $relIndexToRId[$index] = (string) $idAttr;
                }
                $index++;
            }
        }

        // 4. richValueRel.xml.rels: r:id -> actual media Target
        $relsNode = simplexml_load_string($richValueRelRelsXml);
        $rIdToTarget = [];
        if ($relsNode !== false) {
            foreach ($relsNode->Relationship as $rel) {
                $rIdToTarget[(string) $rel['Id']] = self::resolveTarget('xl/richData', (string) $rel['Target']);
            }
        }

        // 5. Walk the sheet's cells, find every `vm` attribute, and follow
        //    the chain above to a media file + its row number.
        $sheetNode = simplexml_load_string($sheetXml);
        $results = [];

        if ($sheetNode !== false) {
            foreach ($sheetNode->sheetData->row as $row) {
                $rowNumber = (int) $row['r'];

                foreach ($row->c as $cell) {
                    if (!isset($cell['vm'])) {
                        continue;
                    }

                    $bkIndex = ((int) $cell['vm']) - 1;
                    $richValueIndex = $bkToRichValueIndex[$bkIndex] ?? null;
                    $relIndex = $richValueIndex !== null ? ($richValueToRelIndex[$richValueIndex] ?? null) : null;
                    $rId = $relIndex !== null ? ($relIndexToRId[$relIndex] ?? null) : null;
                    $mediaPath = $rId !== null ? ($rIdToTarget[$rId] ?? null) : null;

                    if ($mediaPath === null) {
                        continue;
                    }

                    $contents = $zip->getFromName($mediaPath);
                    if ($contents === false) {
                        continue;
                    }

                    $results[] = [
                        'row'      => $rowNumber,
                        'name'     => basename($mediaPath),
                        'contents' => $contents,
                        'mime'     => ImageHelper::mimeFromBinary($contents),
                    ];
                }
            }
        }

        $zip->close();

        return $results;
    }

    private static function findSheetByName(array $sheets, string $name): ?array
    {
        foreach ($sheets as $sheet) {
            if (strcasecmp($sheet['name'], $name) === 0) {
                return $sheet;
            }
        }

        return null;
    }

    /**
     * @return array<int, string> shared string table, index-aligned
     */
    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = self::readEntry($zip, 'xl/sharedStrings.xml', required: false);

        if ($xml === null) {
            return [];
        }

        $node = simplexml_load_string($xml);

        if ($node === false) {
            return [];
        }

        $strings = [];
        foreach ($node->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }

            // Rich text: concatenate all <r><t> runs
            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private static function cellValue(\SimpleXMLElement $cell, string $type, array $sharedStrings): ?string
    {
        if ($type === 's') {
            $index = (int) $cell->v;
            return $sharedStrings[$index] ?? null;
        }

        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string) $cell->is->t : null;
        }

        if ($type === 'b') {
            return ((string) $cell->v) === '1' ? 'true' : 'false';
        }

        if (!isset($cell->v)) {
            return null;
        }

        return (string) $cell->v;
    }

    /**
     * "B12" -> "B"
     */
    private static function columnLetters(string $cellRef): string
    {
        preg_match('/^[A-Z]+/', $cellRef, $matches);

        return $matches[0] ?? 'A';
    }

    private static function open(string $filePath): ZipArchive
    {
        if (!is_file($filePath)) {
            throw new ImportException("Excel file not found: {$filePath}");
        }

        $zip = new ZipArchive();
        $result = $zip->open($filePath);

        if ($result !== true) {
            throw new ImportException('The uploaded file is not a valid .xlsx workbook.');
        }

        return $zip;
    }

    private static function readEntry(ZipArchive $zip, string $entryName, bool $required = true): ?string
    {
        $contents = $zip->getFromName($entryName);

        if ($contents === false) {
            if ($required) {
                throw new ImportException("Malformed workbook: missing {$entryName}.");
            }

            return null;
        }

        return $contents;
    }
}
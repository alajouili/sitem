<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Repositories\ImportRepository;
use App\Requests\ImportRequest;
use App\Services\ArchiveService;
use App\Services\ExcelImportService;
use App\Services\ImageExtractionService;
use App\Storage\LocalDiskStorage;
use App\Storage\StorageInterface;

final class ImportController
{
    private StorageInterface $storage;
    private ExcelImportService $importService;
    private ImportRepository $imports;

    public function __construct(?ImportRepository $imports = null, ?StorageInterface $storage = null)
    {
        $this->imports = $imports ?? new ImportRepository();
        $this->storage = $storage ?? new LocalDiskStorage();
        $this->importService = new ExcelImportService(
            $this->storage,
            $this->imports,
            new ArchiveService(storage: $this->storage),
            new ImageExtractionService($this->storage)
        );
    }

    /**
     * Uploads and synchronously processes an .xlsx file: parses rows,
     * creates an Archive per valid row, extracts + links embedded
     * images, and returns the final Import status including any
     * per-row errors.
     */
    public function store(Request $request): Response
    {
        $importRequest = ImportRequest::fromRequest($request);
        $actor = $request->getAttribute('user');

        $import = $this->importService->stageUpload($importRequest->file, $actor?->id);
        $result = $this->importService->run($import, $actor?->id);

        return Response::success($result->toArray(), 'Import processed.', 201);
    }

    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);
        $actor = $request->getAttribute('user');

        // Non-admins only see their own imports; admins see everything.
        $userId = ($actor !== null && !$actor->isAdmin()) ? $actor->id : null;

        $result = $this->imports->paginate($page, $perPage, $userId);

        return Response::success([
            'items' => array_map(fn ($i) => $i->toArray(), $result['data']),
            'total' => $result['total'],
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->routeParam('id');
        $import = $this->imports->findById($id);

        if ($import === null) {
            throw new NotFoundException("Import #{$id} not found.");
        }

        return Response::success($import->toArray());
    }
}
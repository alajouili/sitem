<?php

declare(strict_types=1);

namespace App\Resources;

final class ReportResource
{
    private function __construct(private readonly array $summary)
    {
    }

    public static function make(array $summary): self
    {
        return new self($summary);
    }

    public function toArray(): array
    {
        return [
            'archives_total'     => $this->summary['archives_total'] ?? 0,
            'archives_by_status' => $this->summary['archives_by_status'] ?? [],
            'generated_at'       => $this->summary['generated_at'] ?? date(DATE_ATOM),
        ];
    }
}
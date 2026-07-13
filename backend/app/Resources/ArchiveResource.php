<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Archive;
use App\Models\Image;
use App\Storage\StorageInterface;

final class ArchiveResource
{
    /** @var Image[]|null */
    private ?array $images = null;

    private function __construct(
        private readonly Archive $archive,
        private readonly ?StorageInterface $storage = null
    ) {
    }

    public static function make(Archive $archive, ?StorageInterface $storage = null): self
    {
        return new self($archive, $storage);
    }

    /**
     * @param Image[] $images
     */
    public function withImages(array $images): self
    {
        $this->images = $images;

        return $this;
    }

    public function toArray(): array
    {
        $data = $this->archive->toArray();

        if ($this->images !== null) {
            $data['images'] = ImageResource::collection($this->images, $this->storage);
        }

        return $data;
    }

    /**
     * @param Archive[] $archives
     */
    public static function collection(array $archives, ?StorageInterface $storage = null): array
    {
        return array_map(fn (Archive $a) => self::make($a, $storage)->toArray(), $archives);
    }
}
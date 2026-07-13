<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Image;
use App\Storage\StorageInterface;

final class ImageResource
{
    private function __construct(
        private readonly Image $image,
        private readonly ?StorageInterface $storage
    ) {
    }

    public static function make(Image $image, ?StorageInterface $storage = null): self
    {
        return new self($image, $storage);
    }

    public function toArray(): array
    {
        $data = $this->image->toArray();
        $data['url'] = $this->storage?->url($this->image->path);

        return $data;
    }

    /**
     * @param Image[] $images
     */
    public static function collection(array $images, ?StorageInterface $storage = null): array
    {
        return array_map(fn (Image $i) => self::make($i, $storage)->toArray(), $images);
    }
}
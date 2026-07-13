<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Repositories\ImageRepository;
use App\Resources\ImageResource;
use App\Services\ImageExtractionService;
use App\Storage\LocalDiskStorage;
use App\Storage\StorageInterface;

final class ImageController
{
    private StorageInterface $storage;
    private ImageExtractionService $extraction;
    private ImageRepository $images;

    public function __construct(?ImageRepository $images = null, ?StorageInterface $storage = null)
    {
        $this->images = $images ?? new ImageRepository();
        $this->storage = $storage ?? new LocalDiskStorage();
        $this->extraction = new ImageExtractionService($this->storage, $this->images);
    }

    /**
     * Streams the raw image bytes for direct display (e.g. <img src>
     * pointed at /api/images/{id}/raw). Metadata endpoints use
     * ImageResource for JSON instead.
     */
    public function raw(Request $request): Response
    {
        $image = $this->findOrFail((int) $request->routeParam('id'));
        $contents = $this->storage->get($image->path);

        return Response::text($contents)->withHeader('Content-Type', $image->mimeType);
    }

    public function show(Request $request): Response
    {
        $image = $this->findOrFail((int) $request->routeParam('id'));

        return Response::success(ImageResource::make($image, $this->storage)->toArray());
    }

    /**
     * Attaches a direct image upload to an archive (as opposed to images
     * extracted automatically from an Excel import).
     */
    public function store(Request $request): Response
    {
        $archiveId = $request->input('archive_id') !== null ? (int) $request->input('archive_id') : null;
        $file = $request->file('image');

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return Response::error('A valid image file is required.', 422);
        }

        $image = $this->extraction->storeUploadedImage($file, $archiveId);

        return Response::success(ImageResource::make($image, $this->storage)->toArray(), 'Image uploaded.', 201);
    }

    public function destroy(Request $request): Response
    {
        $image = $this->findOrFail((int) $request->routeParam('id'));

        $this->storage->delete($image->path);
        $this->images->delete($image->id);

        return Response::success(null, 'Image deleted.');
    }

    private function findOrFail(int $id): \App\Models\Image
    {
        $image = $this->images->findById($id);

        if ($image === null) {
            throw new NotFoundException("Image #{$id} not found.");
        }

        return $image;
    }
}
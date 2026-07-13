<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Requests\ArchiveRequest;
use App\Resources\ArchiveResource;
use App\Resources\ImageResource;
use App\Services\ArchiveService;
use App\Storage\LocalDiskStorage;
use App\Storage\StorageInterface;

final class ArchiveController
{
    private StorageInterface $storage;
    private ArchiveService $archives;

    public function __construct(?ArchiveService $archives = null, ?StorageInterface $storage = null)
    {
        $this->archives = $archives ?? new ArchiveService();
        $this->storage = $storage ?? new LocalDiskStorage();
    }

    public function index(Request $request): Response
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        $filters = array_filter([
            'category' => $request->query('category'),
            'status'   => $request->query('status'),
            'search'   => $request->query('search'),
        ]);

        $result = $this->archives->list($page, $perPage, $filters);

        return Response::success([
            'items' => ArchiveResource::collection($result['data'], $this->storage),
            'total' => $result['total'],
            'page'  => $result['page'],
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->routeParam('id');
        $archive = $this->archives->find($id);
        $images = $this->archives->imagesFor($id);

        return Response::success(
            ArchiveResource::make($archive, $this->storage)->withImages($images)->toArray()
        );
    }

    public function store(Request $request): Response
    {
        $validated = ArchiveRequest::forCreate($request);
        $actor = $request->getAttribute('user');

        $archive = $this->archives->create($validated->data, $actor?->id);

        return Response::success(ArchiveResource::make($archive, $this->storage)->toArray(), 'Archive created.', 201);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->routeParam('id');
        $validated = ArchiveRequest::forUpdate($request);
        $actor = $request->getAttribute('user');

        $archive = $this->archives->update($id, $validated->data, $actor?->id);

        return Response::success(ArchiveResource::make($archive, $this->storage)->toArray(), 'Archive updated.');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->routeParam('id');
        $actor = $request->getAttribute('user');

        $this->archives->delete($id, $actor?->id);

        return Response::success(null, 'Archive deleted.');
    }

    public function images(Request $request): Response
    {
        $id = (int) $request->routeParam('id');
        $images = $this->archives->imagesFor($id);

        return Response::success(ImageResource::collection($images, $this->storage));
    }
}
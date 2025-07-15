<?php

namespace App\Http\Controllers\Admin\api\v1\equipment;

use App\Models\StorageEquipment;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\equipment\StorageEquipmentResource;
use App\Repositories\Admin\api\v1\equipment\StorageEquipmentRepository;
use App\Http\Resources\Admin\api\v1\equipment\StorageEquipmentCollection;
use App\Http\Requests\Admin\api\v1\equipment\StoreStorageEquipmentRequest;
use App\Http\Requests\Admin\api\v1\equipment\UpdateStorageEquipmentRequest;

class StorageEquipmentController extends Controller
{
    protected $storageEquipmentRepository;

    public function __construct(StorageEquipmentRepository $storageEquipmentRepository)
    {
        $this->storageEquipmentRepository = $storageEquipmentRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): StorageEquipmentCollection
    {
        $storages = StorageEquipment::with(['supplier','zone'])->get();
        return new StorageEquipmentCollection($storages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStorageEquipmentRequest $request): StorageEquipmentResource
    {
        $validated = $request->validated();
        $validated['safety_features'] = json_encode($validated['safety_features']);
        $storage = $this->storageEquipmentRepository->create($validated);
        return new StorageEquipmentResource($storage);
    }

    /**
     * Display the specified resource.
     */
    public function show(StorageEquipment $storage): StorageEquipmentResource
    {
        $storage = $this->storageEquipmentRepository->get($storage);
        return new StorageEquipmentResource($storage->load(['supplier','zone']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStorageEquipmentRequest $request, StorageEquipment $storage_equipment): StorageEquipmentResource
    {
        $validated = $request->validated();
        $validated['safety_features'] = json_encode($validated['safety_features']);
        $storage = $this->storageEquipmentRepository->update($storage_equipment, $validated);
        return new StorageEquipmentResource($storage);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StorageEquipment $storage)
    {
        $this->storageEquipmentRepository->delete($storage);
        return response()->json([
            'status' => true,
            'message' => 'Storage Equipment deleted successfully.',
        ]);
    }
}

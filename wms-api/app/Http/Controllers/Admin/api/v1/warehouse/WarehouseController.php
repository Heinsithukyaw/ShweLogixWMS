<?php

namespace App\Http\Controllers\Admin\api\v1\warehouse;

use App\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\warehouse\WarehouseResource;
use App\Repositories\Admin\api\v1\warehouse\WarehouseRepository;
use App\Http\Resources\Admin\api\v1\warehouse\WarehouseCollection;
use App\Http\Requests\Admin\api\v1\warehouse\StoreWarehouseRequest;
use App\Http\Requests\Admin\api\v1\warehouse\UpdateWarehouseRequest;

class WarehouseController extends Controller
{
    protected $warehouseRepository;

    public function __construct(WarehouseRepository $warehouseRepository)
    {
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): WarehouseCollection
    {
        // $categories = $this->categoryRepository->getAll();
        $warehouses = Warehouse::all();
        return new WarehouseCollection($warehouses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWarehouseRequest $request): WarehouseResource
    {
        $warehouse = $this->warehouseRepository->create($request->validated());
        return new WarehouseResource($warehouse);
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse): WarehouseResource
    {
        $warehouse = $this->warehouseRepository->get($warehouse);
        return new WarehouseResource($warehouse->load('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): WarehouseResource
    {
        $warehouse = $this->warehouseRepository->update($warehouse, $request->validated());
        return new WarehouseResource($warehouse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $this->warehouseRepository->delete($warehouse);
        return response()->json([
            'status' => true,
            'message' => 'Warehouse deleted successfully.',
        ]);
    }
}

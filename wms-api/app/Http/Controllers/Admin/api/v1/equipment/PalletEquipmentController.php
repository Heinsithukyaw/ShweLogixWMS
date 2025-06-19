<?php

namespace App\Http\Controllers\Admin\api\v1\equipment;

use App\Models\PalletEquipment;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\equipment\PalletEquipmentResource;
use App\Repositories\Admin\api\v1\equipment\PalletEquipmentRepository;
use App\Http\Resources\Admin\api\v1\equipment\PalletEquipmentCollection;
use App\Http\Requests\Admin\api\v1\equipment\StorePalletEquipmentRequest;
use App\Http\Requests\Admin\api\v1\equipment\UpdatePalletEquipmentRequest;

class PalletEquipmentController extends Controller
{
    protected $palletEquipmentRepository;

    public function __construct(PalletEquipmentRepository $palletEquipmentRepository)
    {
        $this->palletEquipmentRepository = $palletEquipmentRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): PalletEquipmentCollection
    {
        $pallets = PalletEquipment::all();
        return new PalletEquipmentCollection($pallets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePalletEquipmentRequest $request): PalletEquipmentResource
    {
        $pallet = $this->palletEquipmentRepository->create($request->validated());
        return new PalletEquipmentResource($pallet);
    }

    /**
     * Display the specified resource.
     */
    public function show(PalletEquipment $pallet): PalletEquipmentResource
    {
        $pallet = $this->palletEquipmentRepository->get($pallet);
        return new PalletEquipmentResource($pallet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePalletEquipmentRequest $request, PalletEquipment $pallet_equipment): PalletEquipmentResource
    {
        $pallet = $this->palletEquipmentRepository->update($pallet_equipment, $request->validated());
        return new PalletEquipmentResource($pallet);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PalletEquipment $pallet_equipment)
    {
        $this->palletEquipmentRepository->delete($pallet_equipment);
        return response()->json([
            'status' => true,
            'message' => 'Pallet Equipment deleted successfully.',
        ]);
    }
}

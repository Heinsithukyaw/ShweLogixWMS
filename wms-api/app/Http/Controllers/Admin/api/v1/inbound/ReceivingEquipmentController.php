<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\ReceivingEquipment;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingEquipmentResource;
use App\Repositories\Admin\api\v1\inbound\ReceivingEquipmentRepository;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingEquipmentCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreReceivingEquipmentRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateReceivingEquipmentRequest;

class ReceivingEquipmentController extends Controller
{
    protected $receivingEquipmentRepository;

    public function __construct(ReceivingEquipmentRepository $receivingEquipmentRepository)
    {
        $this->receivingEquipmentRepository = $receivingEquipmentRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ReceivingEquipmentCollection
    {
        $receiving_equipments = ReceivingEquipment::with(['assigned_emp'])->orderBy('id','desc')->get();
        return new ReceivingEquipmentCollection($receiving_equipments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReceivingEquipmentRequest $request): ReceivingEquipmentResource
    {
        $receiving_equipment = $this->receivingEquipmentRepository->create($request->validated());
        return new ReceivingEquipmentResource($receiving_equipment);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(ReceivingEquipment $receivingEquipment): ReceivingEquipmentResource
    {
        $receiving_equipment = $this->receivingEquipmentRepository->get($receivingEquipment);
        return new ReceivingEquipmentResource($receiving_equipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceivingEquipmentRequest $request, ReceivingEquipment $receivingEquipment): ReceivingEquipmentResource
    {
        $update_receiving_equipment = $this->receivingEquipmentRepository->update($receivingEquipment, $request->validated());
        return new ReceivingEquipmentResource($update_receiving_equipment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReceivingEquipment $delete_receiving_equipment)
    {
        $this->receivingEquipmentRepository->delete($delete_receiving_equipment);
        return response()->json([
            'status' => true,
            'message' => 'Receiving Equipment deleted successfully.',
        ]);
    }
}

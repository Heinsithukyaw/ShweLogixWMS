<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\InboundShipment;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\InboundShipmentResource;
use App\Repositories\Admin\api\v1\inbound\InboundShipmentRepository;
use App\Http\Resources\Admin\api\v1\inbound\InboundShipmentCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreInboundShipmentRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateInboundShipmentRequest;

class InboundShipmentController extends Controller
{
    protected $inboundShipmentRepository;

    public function __construct(InboundShipmentRepository $inboundShipmentRepository)
    {
        $this->inboundShipmentRepository = $inboundShipmentRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): InboundShipmentCollection
    {
        $inbound_shipments = InboundShipment::with(['supplier','carrier','stagingLocation'])->get();
        return new InboundShipmentCollection($inbound_shipments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInboundShipmentRequest $request): InboundShipmentResource
    {
        $inbound_shipment = $this->inboundShipmentRepository->create($request->validated());
        return new InboundShipmentResource($inbound_shipment);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(InboundShipment $inbound_shipment): InboundShipmentResource
    {
        $inbound_shipment = $this->inboundShipmentRepository->get($inbound_shipment);
        return new InboundShipmentResource($inbound_shipment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInboundShipmentRequest $request, InboundShipment $inboundShipment): InboundShipmentResource
    {
        $update_inbound_shipment = $this->inboundShipmentRepository->update($inboundShipment, $request->validated());
        return new InboundShipmentResource($update_inbound_shipment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InboundShipment $inboundShipment)
    {
        $this->inboundShipmentRepository->delete($inboundShipment);
        return response()->json([
            'status' => true,
            'message' => 'Inbound Shipment deleted successfully.',
        ]);
    }
}

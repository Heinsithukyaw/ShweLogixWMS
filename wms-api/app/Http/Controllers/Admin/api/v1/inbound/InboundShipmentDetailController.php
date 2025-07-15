<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\InboundShipmentDetail;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\InboundShipmentDetailResource;
use App\Repositories\Admin\api\v1\inbound\InboundShipmentDetailRepository;
use App\Http\Resources\Admin\api\v1\inbound\InboundShipmentDetailCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreInboundShipmentDetailRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateInboundShipmentDetailRequest;

class InboundShipmentDetailController extends Controller
{
    protected $inboundShipmentDetailRepository;

    public function __construct(InboundShipmentDetailRepository $inboundShipmentDetailRepository)
    {
        $this->inboundShipmentDetailRepository = $inboundShipmentDetailRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): InboundShipmentDetailCollection
    {
        $inbound_shipment_details = InboundShipmentDetail::with(['inbound_shipment','product','location'])->get();
        return new InboundShipmentDetailCollection($inbound_shipment_details);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInboundShipmentDetailRequest $request): InboundShipmentDetailResource
    {
        $inbound_shipment_detail = $this->inboundShipmentDetailRepository->create($request->validated());
        return new InboundShipmentDetailResource($inbound_shipment_detail);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(InboundShipmentDetail $inbound_shipment_detail): InboundShipmentDetailResource
    {
        $inbound_shipment_detail = $this->inboundShipmentDetailRepository->get($inbound_shipment_detail);
        return new InboundShipmentDetailResource($inbound_shipment_detail);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInboundShipmentDetailRequest $request, InboundShipmentDetail $inboundShipmentDetail): InboundShipmentDetailResource
    {
        $update_inbound_shipment_detail = $this->inboundShipmentDetailRepository->update($inboundShipmentDetail, $request->validated());
        return new InboundShipmentDetailResource($update_inbound_shipment_detail);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InboundShipmentDetail $delete_inbound_shipment_detail)
    {
        $this->inboundShipmentDetailRepository->delete($delete_inbound_shipment_detail);
        return response()->json([
            'status' => true,
            'message' => 'Inbound Shipment Detail deleted successfully.',
        ]);
    }
}

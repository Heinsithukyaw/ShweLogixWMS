<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\ReceivingLaborTracking;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingLaborTrackingResource;
use App\Repositories\Admin\api\v1\inbound\ReceivingLaborTrackingRepository;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingLaborTrackingCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreReceivingLaborTrackingRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateReceivingLaborTrackingRequest;

class ReceivingLaborTrackingController extends Controller
{
    protected $receivingLaborTrackingRepository;

    public function __construct(ReceivingLaborTrackingRepository $receivingLaborTrackingRepository)
    {
        $this->receivingLaborTrackingRepository = $receivingLaborTrackingRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ReceivingLaborTrackingCollection
    {
        $receiving_labor_trackings = ReceivingLaborTracking::with(['employee','inbound_shipment'])->get();
        return new ReceivingLaborTrackingCollection($receiving_labor_trackings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReceivingLaborTrackingRequest $request): ReceivingLaborTrackingResource
    {
        $receiving_labor_tracking = $this->receivingLaborTrackingRepository->create($request->validated());
        return new ReceivingLaborTrackingResource($receiving_labor_tracking);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(ReceivingLaborTracking $receiving_labor_tracking): ReceivingLaborTrackingResource
    {
        $receiving_labor_tracking = $this->receivingLaborTrackingRepository->get($receiving_labor_tracking);
        return new ReceivingLaborTrackingResource($receiving_labor_tracking);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceivingLaborTrackingRequest $request, ReceivingLaborTracking $receivingLaborTracking): ReceivingLaborTrackingResource
    {
        $update_receiving_labor_tracking = $this->receivingLaborTrackingRepository->update($receivingLaborTracking, $request->validated());
        return new ReceivingLaborTrackingResource($update_receiving_labor_tracking);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReceivingLaborTracking $delete_receiving_labor_tracking)
    {
        $this->receivingLaborTrackingRepository->delete($delete_receiving_labor_tracking);
        return response()->json([
            'status' => true,
            'message' => 'Receiving Labor Tracking deleted successfully.',
        ]);
    }
}

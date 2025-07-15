<?php

namespace App\Http\Controllers\Admin\api\v1\shipping;

use App\Models\ShippingCarrier;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\shipping\shippingCarrierResource;
use App\Repositories\Admin\api\v1\shipping\shippingCarrierRepository;
use App\Http\Resources\Admin\api\v1\shipping\shippingCarrierCollection;
use App\Http\Requests\Admin\api\v1\shipping\StoreShippingCarrierRequest;
use App\Http\Requests\Admin\api\v1\shipping\UpdateShippingCarrierRequest;

class ShippingCarrierController extends Controller
{
    protected $shippingCarrierRepository;

    public function __construct(shippingCarrierRepository $shippingCarrierRepository)
    {
        $this->shippingCarrierRepository = $shippingCarrierRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ShippingCarrierCollection
    {
        $shipping_carriers = ShippingCarrier::all();
        return new ShippingCarrierCollection($shipping_carriers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShippingCarrierRequest $request): ShippingCarrierResource
    {
        $shipping_carrier = $this->shippingCarrierRepository->create($request->validated());
        return new ShippingCarrierResource($shipping_carrier);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShippingCarrier $shipping_carrier): ShippingCarrierResource
    {
        $shipping_carrier = $this->shippingCarrierRepository->get($shipping_carrier);
        return new ShippingCarrierResource($shipping_carrier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShippingCarrierRequest $request, ShippingCarrier $shippingCarrier): ShippingCarrierResource
    {
        $shipping_carrier = $this->shippingCarrierRepository->update($shippingCarrier, $request->validated());
        return new ShippingCarrierResource($shipping_carrier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShippingCarrier $shippingCarrier)
    {
        $this->shippingCarrierRepository->delete($shippingCarrier);
        return response()->json([
            'status' => true,
            'message' => 'Shipping Carrier deleted successfully.',
        ]);
    }
}

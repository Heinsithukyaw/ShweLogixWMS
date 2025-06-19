<?php

namespace App\Http\Controllers\Admin\api\v1\order;

use App\Models\OrderType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\order\OrderTypeResource;
use App\Repositories\Admin\api\v1\order\OrderTypeRepository;
use App\Http\Resources\Admin\api\v1\order\OrderTypeCollection;
use App\Http\Requests\Admin\api\v1\order\StoreOrderTypeRequest;
use App\Http\Requests\Admin\api\v1\order\UpdateOrderTypeRequest;

class OrderTypeController extends Controller
{
    protected $orderTypeRepository;

    public function __construct(OrderTypeRepository $orderTypeRepository)
    {
        $this->orderTypeRepository = $orderTypeRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): OrderTypeCollection
    {
        $order_types = OrderType::all();
        return new OrderTypeCollection($order_types);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderTypeRequest $request): OrderTypeResource
    {
        $order_type = $this->orderTypeRepository->create($request->validated());
        return new OrderTypeResource($order_type);
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderType $order_type): OrderTypeResource
    {
        $order_type = $this->orderTypeRepository->get($order_type);
        return new OrderTypeResource($order_type);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderTypeRequest $request, OrderType $orderType): OrderTypeResource
    {
        $order_type = $this->orderTypeRepository->update($orderType, $request->validated());
        return new OrderTypeResource($order_type);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderType $order_type)
    {
        $this->orderTypeRepository->delete($order_type);
        return response()->json([
            'status' => true,
            'message' => 'Order Type deleted successfully.',
        ]);
    }
}

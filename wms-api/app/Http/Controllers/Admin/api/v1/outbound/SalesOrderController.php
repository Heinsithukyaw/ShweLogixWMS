<?php

namespace App\Http\Controllers\Admin\api\v1\outbound;

use App\Models\SalesOrder;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\outbound\SalesOrderResource;
use App\Repositories\Admin\api\v1\outbound\SalesOrderRepository;
use App\Http\Resources\Admin\api\v1\outbound\SalesOrderCollection;
use App\Http\Requests\Admin\api\v1\outbound\StoreSalesOrderRequest;
use App\Http\Requests\Admin\api\v1\outbound\UpdateSalesOrderRequest;

class SalesOrderController extends Controller
{
    protected $salesOrderRepository;

    public function __construct(SalesOrderRepository $salesOrderRepository)
    {
        $this->salesOrderRepository = $salesOrderRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): SalesOrderCollection
    {
        $sales_orders = SalesOrder::with(['customer'])->orderBy('id', 'desc')->get();
        return new SalesOrderCollection($sales_orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalesOrderRequest $request): SalesOrderResource
    {
        $salesOrder = $this->salesOrderRepository->create($request->validated());
        return new SalesOrderResource($salesOrder);
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesOrder $salesOrder): SalesOrderResource
    {
        $salesOrder = $this->salesOrderRepository->get($salesOrder);
        return new SalesOrderResource($salesOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): SalesOrderResource
    {
        $salesOrder = $this->salesOrderRepository->update($salesOrder, $request->validated());
        return new SalesOrderResource($salesOrder);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesOrder $salesOrder)
    {
        $this->salesOrderRepository->delete($salesOrder);
        return response()->json([
            'status' => true,
            'message' => 'Sales Order deleted successfully.',
        ]);
    }
} 
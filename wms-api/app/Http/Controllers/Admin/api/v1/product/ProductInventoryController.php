<?php

namespace App\Http\Controllers\Admin\api\v1\product;

use App\Models\ProductInventory;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\product\ProductInventoryResource;
use App\Repositories\Admin\api\v1\product\ProductInventoryRepository;
use App\Http\Resources\Admin\api\v1\product\ProductInventoryCollection;
use App\Http\Requests\Admin\api\v1\product\StoreProductInventoryRequest;
use App\Http\Requests\Admin\api\v1\product\UpdateProductInventoryRequest;

class ProductInventoryController extends Controller
{
    protected $productInventoryRepository;

    public function __construct(ProductInventoryRepository $productInventoryRepository)
    {
        $this->productInventoryRepository = $productInventoryRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ProductInventoryCollection
    {
        $products = ProductInventory::with('product')->get();
        return new ProductInventoryCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductInventoryRequest $request): ProductInventoryResource
    {
        $product = $this->productInventoryRepository->create($request->validated());
        return new ProductInventoryResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductInventory $productInventory): ProductInventoryResource
    {
        $product = $this->productInventoryRepository->get($product);
        return new ProductInventoryResource($brand->load('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductInventoryRequest $request, ProductInventory $productInventory): ProductInventoryResource
    {
        $product = $this->productInventoryRepository->update($productInventory, $request->validated());
        return new ProductInventoryResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductInventory $productInventory)
    {
        $this->productInventoryRepository->delete($productInventory);
        return response()->json([
            'status' => true,
            'message' => 'Product Inventory Data deleted successfully.',
        ]);
    }
}

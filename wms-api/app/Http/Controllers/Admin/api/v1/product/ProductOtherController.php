<?php

namespace App\Http\Controllers\Admin\api\v1\product;

use App\Models\ProductOther;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\product\ProductOtherResource;
use App\Repositories\Admin\api\v1\product\ProductOtherRepository;
use App\Http\Resources\Admin\api\v1\product\ProductOtherCollection;
use App\Http\Requests\Admin\api\v1\product\StoreProductOtherRequest;
use App\Http\Requests\Admin\api\v1\product\UpdateProductOtherRequest;

class ProductOtherController extends Controller
{
    protected $productOtherRepository;

    public function __construct(ProductOtherRepository $productOtherRepository)
    {
        $this->productOtherRepository = $productOtherRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ProductOtherCollection
    {
        $products = ProductOther::with('product')->get();
        return new ProductOtherCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductOtherRequest $request): ProductOtherResource
    {
        $product = $this->productOtherRepository->create($request->validated());
        return new ProductOtherResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductOther $productOther): ProductOtherResource
    {
        $product = $this->productOtherRepository->get($productOther);
        return new ProductOtherResource($product->load('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductOtherRequest $request, ProductOther $productOther): ProductOtherResource
    {
        $product = $this->productOtherRepository->update($productOther, $request->validated());
        return new ProductOtherResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductOther $productOther)
    {
        $this->productOtherRepository->delete($productOther);
        return response()->json([
            'status' => true,
            'message' => 'Product Other Data deleted successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\api\v1\product;

use App\Models\ProductDimension;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\product\ProductDimensionResource;
use App\Repositories\Admin\api\v1\product\ProductDimensionRepository;
use App\Http\Resources\Admin\api\v1\product\ProductDimensionCollection;
use App\Http\Requests\Admin\api\v1\product\StoreProductDimensionRequest;
use App\Http\Requests\Admin\api\v1\product\UpdateProductDimensionRequest;

class ProductDimensionController extends Controller
{
    protected $productDimensionRepository;

    public function __construct(ProductDimensionRepository $productDimensionRepository)
    {
        $this->productDimensionRepository = $productDimensionRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ProductDimensionCollection
    {
        $products = ProductDimension::with('product')->get();
        return new ProductDimensionCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductDimensionRequest $request): ProductDimensionResource
    {
        $product = $this->productDimensionRepository->create($request->validated());
        return new ProductDimensionResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductDimension $productDimension): ProductDimensionResource
    {
        $product = $this->productDimensionRepository->get($productDimension);
        return new ProductDimensionResource($product->load('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductDimensionRequest $request, ProductDimension $productDimension): ProductDimensionResource
    {
        $product = $this->productDimensionRepository->update($productDimension, $request->validated());
        return new ProductDimensionResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductDimension $productDimension)
    {
        $this->productDimensionRepository->delete($productDimension);
        return response()->json([
            'status' => true,
            'message' => 'Product Dimension Data deleted successfully.',
        ]);
    }
}

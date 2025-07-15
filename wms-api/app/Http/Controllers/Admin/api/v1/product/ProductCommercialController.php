<?php

namespace App\Http\Controllers\Admin\api\v1\product;

use App\Models\ProductCommercial;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\product\ProductCommercialResource;
use App\Repositories\Admin\api\v1\product\ProductCommercialRepository;
use App\Http\Resources\Admin\api\v1\product\ProductCommercialCollection;
use App\Http\Requests\Admin\api\v1\product\StoreProductCommercialRequest;
use App\Http\Requests\Admin\api\v1\product\UpdateProductCommercialRequest;

class ProductCommercialController extends Controller
{
    protected $productCommercialRepository;

    public function __construct(ProductCommercialRepository $productCommercialRepository)
    {
        $this->productCommercialRepository = $productCommercialRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ProductCommercialCollection
    {
        $products = ProductCommercial::with(['product','supplier'])->get();
        return new ProductCommercialCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductCommercialRequest $request): ProductCommercialResource
    {
        $product = $this->productCommercialRepository->create($request->validated());
        return new ProductCommercialResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCommercial $productCommercial): ProductCommercialResource
    {
        $product = $this->productCommercialRepository->get($productCommercial);
        return new ProductCommercialResource($product->load('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductCommercialRequest $request, ProductCommercial $productCommercial): ProductCommercialResource
    {
        $product = $this->productCommercialRepository->update($productCommercial, $request->validated());
        return new ProductCommercialResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCommercial $productCommercial)
    {
        $this->productCommercialRepository->delete($productCommercial);
        return response()->json([
            'status' => true,
            'message' => 'Product Commercial Data deleted successfully.',
        ]);
    }
}

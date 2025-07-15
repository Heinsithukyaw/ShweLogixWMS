<?php

namespace App\Http\Controllers\Admin\api\v1\product;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\product\ProductResource;
use App\Repositories\Admin\api\v1\product\ProductRepository;
use App\Http\Resources\Admin\api\v1\product\ProductCollection;
use App\Http\Requests\Admin\api\v1\product\StoreProductRequest;
use App\Http\Requests\Admin\api\v1\product\UpdateProductRequest;
use App\Services\EventService;

class ProductController extends Controller
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ProductCollection
    {
        $products = Product::with([
            'category:id,category_code,category_name',
            'subcategory:id,category_code,category_name',
            'brand:id,brand_code,brand_name'
        ])->get();
        return new ProductCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->productRepository->create($request->validated());
        
        // Dispatch product created event
        EventService::productCreated($product);
        
        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        $product = $this->productRepository->get($product);
        return new ProductResource($product->load(['category', 'subcategory', 'brand']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product = $this->productRepository->update($product, $request->validated());
        
        // Dispatch product updated event
        EventService::productUpdated($product);
        
        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $this->productRepository->delete($product);
        return response()->json([
            'status' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}

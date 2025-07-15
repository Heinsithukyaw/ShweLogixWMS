<?php

namespace App\Http\Controllers\Admin\api\v1\brand;

use App\Models\Brand;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\brand\BrandResource;
use App\Repositories\Admin\api\v1\brand\BrandRepository;
use App\Http\Resources\Admin\api\v1\brand\BrandCollection;
use App\Http\Requests\Admin\api\v1\brand\StoreBrandRequest;
use App\Http\Requests\Admin\api\v1\brand\UpdateBrandRequest;

class BrandController extends Controller
{
    protected $brandRepository;

    public function __construct(BrandRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): BrandCollection
    {
        // $categories = $this->categoryRepository->getAll();
        $brands = Brand::with([
            'category:id,category_code,category_name',
            'subcategory:id,category_code,category_name'
        ])->get();
        return new BrandCollection($brands);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrandRequest $request): BrandResource
    {
        $brand = $this->brandRepository->create($request->validated());
        return new BrandResource($brand);
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand): BrandResource
    {
        $brand = $this->brandRepository->get($brand);
        return new BrandResource($brand->load('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        $brand = $this->brandRepository->update($brand, $request->validated());
        return new BrandResource($brand);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $this->brandRepository->delete($brand);
        return response()->json([
            'status' => true,
            'message' => 'Brand deleted successfully.',
        ]);
    }
}

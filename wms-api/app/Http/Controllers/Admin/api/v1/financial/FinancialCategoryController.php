<?php

namespace App\Http\Controllers\Admin\api\v1\financial;

use App\Models\FinancialCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\financial\FinancialCategoryResource;
use App\Repositories\Admin\api\v1\financial\FinancialCategoryRepository;
use App\Http\Resources\Admin\api\v1\financial\FinancialCategoryCollection;
use App\Http\Requests\Admin\api\v1\financial\StoreFinancialCategoryRequest;
use App\Http\Requests\Admin\api\v1\financial\UpdateFinancialCategoryRequest;

class FinancialCategoryController extends Controller
{
    protected $financialCategoryRepository;

    public function __construct(FinancialCategoryRepository $financialCategoryRepository)
    {
        $this->financialCategoryRepository = $financialCategoryRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): FinancialCategoryCollection
    {
        $financial_categories = FinancialCategory::all();
        return new FinancialCategoryCollection($financial_categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFinancialCategoryRequest $request): FinancialCategoryResource
    {
        $financial_category = $this->financialCategoryRepository->create($request->validated());
        return new FinancialCategoryResource($financial_category);
    }

    /**
     * Display the specified resource.
     */
    public function show(FinancialCategory $financialCategory): FinancialCategoryResource
    {
        $financial_category = $this->financialCategoryRepository->get($financialCategory);
        return new FinancialCategoryResource($financial_category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFinancialCategoryRequest $request, FinancialCategory $financialCategory): FinancialCategoryResource
    {
        $financial_category = $this->financialCategoryRepository->update($financialCategory, $request->validated());
        return new FinancialCategoryResource($financial_category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialCategory $financialCategory)
    {
        $this->financialCategoryRepository->delete($financialCategory);
        return response()->json([
            'status' => true,
            'message' => 'Financial Category deleted successfully.',
        ]);
    }
}

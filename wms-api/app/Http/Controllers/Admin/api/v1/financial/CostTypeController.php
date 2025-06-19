<?php

namespace App\Http\Controllers\Admin\api\v1\financial;

use App\Models\CostType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\financial\CostTypeResource;
use App\Repositories\Admin\api\v1\financial\CostTypeRepository;
use App\Http\Resources\Admin\api\v1\financial\CostTypeCollection;
use App\Http\Requests\Admin\api\v1\financial\StoreCostTypeRequest;
use App\Http\Requests\Admin\api\v1\financial\UpdateCostTypeRequest;

class CostTypeController extends Controller
{
    protected $costTypeRepository;

    public function __construct(CostTypeRepository $costTypeRepository)
    {
        $this->costTypeRepository = $costTypeRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): CostTypeCollection
    {
        $cost_types = CostType::with(['cost_category','cost_subcategory'])->get();
        return new CostTypeCollection($cost_types);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCostTypeRequest $request): CostTypeResource
    {
        $cost_types = $this->costTypeRepository->create($request->validated());
        return new CostTypeResource($cost_types);
    }

    /**
     * Display the specified resource.
     */
    public function show(CostType $cost_type): CostTypeResource
    {
        $cost_type = $this->costTypeRepository->get($cost_type);
        return new CostTypeResource($cost_type);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCostTypeRequest $request, CostType $costType): CostTypeResource
    {
        $cost_type = $this->costTypeRepository->update($costType, $request->validated());
        return new CostTypeResource($cost_type);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CostType $costType)
    {
        $this->costTypeRepository->delete($costType);
        return response()->json([
            'status' => true,
            'message' => 'Cost Type deleted successfully.',
        ]);
    }
}

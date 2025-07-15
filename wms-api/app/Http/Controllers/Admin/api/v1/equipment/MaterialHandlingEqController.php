<?php

namespace App\Http\Controllers\Admin\api\v1\equipment;

use App\Models\MaterialHandlingEq;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\equipment\MaterialHandlingEqResource;
use App\Repositories\Admin\api\v1\equipment\MaterialHandlingEqRepository;
use App\Http\Resources\Admin\api\v1\equipment\MaterialHandlingEqCollection;
use App\Http\Requests\Admin\api\v1\equipment\StoreMaterialHandlingEqRequest;
use App\Http\Requests\Admin\api\v1\equipment\UpdateMaterialHandlingEqRequest;

class MaterialHandlingEqController extends Controller
{
    protected $materialHandlingEqRepository;

    public function __construct(MaterialHandlingEqRepository $materialHandlingEqRepository)
    {
        $this->materialHandlingEqRepository = $materialHandlingEqRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): MaterialHandlingEqCollection
    {
        $materials = MaterialHandlingEq::with(['supplier','supplierContact'])->get();
        return new MaterialHandlingEqCollection($materials);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMaterialHandlingEqRequest $request): MaterialHandlingEqResource
    {
        $validated = $request->validated();
        $validated['safety_features'] = json_encode($validated['safety_features']);

        $material = $this->materialHandlingEqRepository->create($validated);
        return new MaterialHandlingEqResource($material);
    }

    /**
     * Display the specified resource.
     */
    public function show(MaterialHandlingEq $material): MaterialHandlingEqResource
    {
        $material = $this->materialHandlingEqRepository->get($material);
        return new MaterialHandlingEqResource($material->load('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMaterialHandlingEqRequest $request, MaterialHandlingEq $material_handling_eq): MaterialHandlingEqResource
    {
        $validated = $request->validated();
        $validated['safety_features'] = json_encode($validated['safety_features']);
        $material_handling = $this->materialHandlingEqRepository->update($material_handling_eq, $validated);
        return new MaterialHandlingEqResource($material_handling);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialHandlingEq $material_handling_eq)
    {
        $this->materialHandlingEqRepository->delete($material_handling_eq);
        return response()->json([
            'status' => true,
            'message' => 'Material Handling Equipment deleted successfully.',
        ]);
    }
}

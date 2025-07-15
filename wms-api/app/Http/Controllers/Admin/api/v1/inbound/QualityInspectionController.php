<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\QualityInspection;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\QualityInspectionResource;
use App\Repositories\Admin\api\v1\inbound\QualityInspectionRepository;
use App\Http\Resources\Admin\api\v1\inbound\QualityInspectionCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreQualityInspectionRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateQualityInspectionRequest;

class QualityInspectionController extends Controller
{
    protected $qualityInspectionRepository;

    public function __construct(QualityInspectionRepository $qualityInspectionRepository)
    {
        $this->qualityInspectionRepository = $qualityInspectionRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): QualityInspectionCollection
    {
        $quality_inspections = QualityInspection::with(['inbound_shipment_detail'])->get();
        return new QualityInspectionCollection($quality_inspections);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQualityInspectionRequest $request): QualityInspectionResource
    {
        
        $validated = $request->validated();
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $path = $request->file('image')->store('uploads/quality-inspection', 'public');
            $validated['image_path'] = $path;
        }
        $quality_inspection = $this->qualityInspectionRepository->create($validated);
        return new QualityInspectionResource($quality_inspection);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(QualityInspection $qualityInspection): QualityInspectionResource
    {
        $quality_inspection = $this->qualityInspectionRepository->get($qualityInspection);
        return new QualityInspectionResource($quality_inspection);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQualityInspectionRequest $request, $id): QualityInspectionResource
    {
        $validated = $request->validated();
    
        $quality_inspection = QualityInspection::findOrFail($id);
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($quality_inspection->image_path) {
                Storage::disk('public')->delete($quality_inspection->image_path);
            }
                $path = $request->file('image')->store('uploads/quality-inspection', 'public');
            $validated['image_path'] = $path;
        }
        $quality_inspection->fill($validated);
        $quality_inspection->save(); 
    
        return new QualityInspectionResource($quality_inspection);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QualityInspection $qualityInspection)
    {
        $this->qualityInspectionRepository->delete($qualityInspection);
        return response()->json([
            'status' => true,
            'message' => 'Quality Inspection deleted successfully.',
        ]);
    }
}

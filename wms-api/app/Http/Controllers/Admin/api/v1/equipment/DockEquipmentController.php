<?php

namespace App\Http\Controllers\Admin\api\v1\equipment;

use App\Models\DockEquipment;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\equipment\DockEquipmentResource;
use App\Repositories\Admin\api\v1\equipment\DockEquipmentRepository;
use App\Http\Resources\Admin\api\v1\equipment\DockEquipmentCollection;
use App\Http\Requests\Admin\api\v1\equipment\StoreDockEquipmentRequest;
use App\Http\Requests\Admin\api\v1\equipment\UpdateDockEquipmentRequest;

class DockEquipmentController extends Controller
{
    protected $dockEquipmentRepository;

    public function __construct(DockEquipmentRepository $dockEquipmentRepository)
    {
        $this->dockEquipmentRepository = $dockEquipmentRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): DockEquipmentCollection
    {
        $docks = DockEquipment::with(['warehouse','area'])->get();
        return new DockEquipmentCollection($docks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDockEquipmentRequest $request): DockEquipmentResource
    {
        $validated = $request->validated();
        $validated['equipment_features'] = json_encode($validated['equipment_features']);
        $dock = $this->dockEquipmentRepository->create($validated);
        return new DockEquipmentResource($dock);
    }

    /**
     * Display the specified resource.
     */
    public function show(DockEquipment $dock): DockEquipmentResource
    {
        $dock = $this->DockEquipmentRepository->get($dock);
        return new DockEquipmentResource($dock);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDockEquipmentRequest $request, DockEquipment $dock_equipment): DockEquipmentResource
    {
        $validated = $request->validated();
        $validated['equipment_features'] = json_encode($validated['equipment_features']);
        $dock = $this->dockEquipmentRepository->update($dock_equipment, $validated);
        return new DockEquipmentResource($dock);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DockEquipment $dock)
    {
        $this->dockEquipmentRepository->delete($dock);
        return response()->json([
            'status' => true,
            'message' => 'Dock Equipment deleted successfully.',
        ]);
    }
}

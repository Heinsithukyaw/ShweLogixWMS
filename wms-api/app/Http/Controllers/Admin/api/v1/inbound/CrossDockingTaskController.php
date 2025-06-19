<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\CrossDockingTask;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\CrossDockingTaskResource;
use App\Repositories\Admin\api\v1\inbound\CrossDockingTaskRepository;
use App\Http\Resources\Admin\api\v1\inbound\CrossDockingTaskCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreCrossDockingTaskRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateCrossDockingTaskRequest;

class CrossDockingTaskController extends Controller
{
    protected $crossDockingTaskRepository;

    public function __construct(CrossDockingTaskRepository $crossDockingTaskRepository)
    {
        $this->crossDockingTaskRepository = $crossDockingTaskRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): CrossDockingTaskCollection
    {
        $put_away_tasks = CrossDockingTask::with(['asn','asn_detail','assigned_emp','source_location','destination_location','product'])->get();
        return new CrossDockingTaskCollection($put_away_tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCrossDockingTaskRequest $request): CrossDockingTaskResource
    {
        $cross_docking_task = $this->crossDockingTaskRepository->create($request->validated());
        return new CrossDockingTaskResource($cross_docking_task);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(CrossDockingTask $crossDockingTask): CrossDockingTaskResource
    {
        $cross_docking_task = $this->crossDockingTaskRepository->get($cross_docking_task);
        return new CrossDockingTaskResource($cross_docking_task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCrossDockingTaskRequest $request, CrossDockingTask $crossDockingTask): CrossDockingTaskResource
    {
        $update_cross_docking_task = $this->crossDockingTaskRepository->update($crossDockingTask, $request->validated());
        return new CrossDockingTaskResource($update_cross_docking_task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CrossDockingTask $delete_cross_docking_task)
    {
        $this->crossDockingTaskRepository->delete($delete_cross_docking_task);
        return response()->json([
            'status' => true,
            'message' => 'CrossDocking Task deleted successfully.',
        ]);
    }
}

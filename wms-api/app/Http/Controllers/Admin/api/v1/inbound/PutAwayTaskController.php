<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\PutAwayTask;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\PutAwayTaskResource;
use App\Repositories\Admin\api\v1\inbound\PutAwayTaskRepository;
use App\Http\Resources\Admin\api\v1\inbound\PutAwayTaskCollection;
use App\Http\Requests\Admin\api\v1\inbound\StorePutAwayTaskRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdatePutAwayTaskRequest;

class PutAwayTaskController extends Controller
{
    protected $putAwayTaskRepository;

    public function __construct(PutAwayTaskRepository $putAwayTaskRepository)
    {
        $this->putAwayTaskRepository = $putAwayTaskRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): PutAwayTaskCollection
    {
        $put_away_tasks = PutAwayTask::with(['inbound_shipment_detail','assigned_emp','source_location','destination_location'])->get();
        return new PutAwayTaskCollection($put_away_tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePutAwayTaskRequest $request): PutAwayTaskResource
    {
        $put_away_task = $this->putAwayTaskRepository->create($request->validated());
        return new PutAwayTaskResource($put_away_task);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(PutAwayTask $putAwayTask): PutAwayTaskResource
    {
        $put_away_task = $this->PutAwayTaskRepository->get($put_away_task);
        return new PutAwayTaskResource($put_away_task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePutAwayTaskRequest $request, PutAwayTask $putAwayTask): PutAwayTaskResource
    {
        $update_put_away_task = $this->putAwayTaskRepository->update($PutAwayTask, $request->validated());
        return new PutAwayTaskResource($update_put_away_task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PutAwayTask $delete_put_away_task)
    {
        $this->putAwayTaskRepository->delete($delete_put_away_task);
        return response()->json([
            'status' => true,
            'message' => 'Putaway Task deleted successfully.',
        ]);
    }
}

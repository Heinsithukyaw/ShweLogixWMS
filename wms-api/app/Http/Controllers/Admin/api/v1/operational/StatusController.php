<?php

namespace App\Http\Controllers\Admin\api\v1\operational;

use App\Models\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\operational\StatusResource;
use App\Repositories\Admin\api\v1\operational\StatusRepository;
use App\Http\Resources\Admin\api\v1\operational\StatusCollection;
use App\Http\Requests\Admin\api\v1\operational\StoreStatusRequest;
use App\Http\Requests\Admin\api\v1\operational\UpdateStatusRequest;

class StatusController extends Controller
{
    protected $statusRepository;

    public function __construct(StatusRepository $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): StatusCollection
    {
        $statuses = status::all();
        return new StatusCollection($statuses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStatusRequest $request): StatusResource
    {
        $status = $this->statusRepository->create($request->validated());
        return new statusResource($status);
    }

    /**
     * Display the specified resource.
     */
    public function show(Status $status): StatusResource
    {
        $status = $this->statusRepository->get($status);
        return new StatusResource($status);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStatusRequest $request, Status $status): StatusResource
    {
        $update_status = $this->statusRepository->update($status, $request->validated());
        return new StatusResource($update_status);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Status $delete_status)
    {
        logger($delete_status);
        $this->statusRepository->delete($delete_status);
        return response()->json([
            'status' => true,
            'message' => 'Status deleted successfully.',
        ]);
    }
}

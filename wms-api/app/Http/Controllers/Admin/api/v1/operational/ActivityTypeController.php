<?php

namespace App\Http\Controllers\Admin\api\v1\operational;

use App\Models\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\operational\ActivityTypeResource;
use App\Repositories\Admin\api\v1\operational\ActivityTypeRepository;
use App\Http\Resources\Admin\api\v1\operational\ActivityTypeCollection;
use App\Http\Requests\Admin\api\v1\operational\StoreActivityTypeRequest;
use App\Http\Requests\Admin\api\v1\operational\UpdateActivityTypeRequest;

class ActivityTypeController extends Controller
{
    protected $activityTypeRepository;

    public function __construct(ActivityTypeRepository $activityTypeRepository)
    {
        $this->activityTypeRepository = $activityTypeRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ActivityTypeCollection
    {
        $activityTypes = ActivityType::all();
        return new ActivityTypeCollection($activityTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityTypeRequest $request): ActivityTypeResource
    {
        $activityType = $this->activityTypeRepository->create($request->validated());
        return new ActivityTypeResource($activityType);
    }

    /**
     * Display the specified resource.
     */
    public function show(ActivityType $activityType): ActivityTypeResource
    {
        $activityType = $this->activityTypeRepository->get($activityType);
        return new ActivityTypeResource($activityType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityTypeRequest $request, ActivityType $activityType): ActivityTypeResource
    {
        $update_activity_type = $this->activityTypeRepository->update($activityType, $request->validated());
        return new ActivityTypeResource($update_activity_Type);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ActivityType $delete_activity_type)
    {
        $this->activityTypeRepository->delete($delete_activity_type);
        return response()->json([
            'status' => true,
            'message' => 'Activity Type deleted successfully.',
        ]);
    }
}

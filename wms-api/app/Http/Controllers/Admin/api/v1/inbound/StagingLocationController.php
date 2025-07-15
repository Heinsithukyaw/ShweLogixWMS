<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\StagingLocation;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\StagingLocationResource;
use App\Repositories\Admin\api\v1\inbound\StagingLocationRepository;
use App\Http\Resources\Admin\api\v1\inbound\StagingLocationCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreStagingLocationRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateStagingLocationRequest;

class StagingLocationController extends Controller
{
    protected $stagingLocationRepository;

    public function __construct(StagingLocationRepository $stagingLocationRepository)
    {
        $this->stagingLocationRepository = $stagingLocationRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): StagingLocationCollection
    {
        $staging_locations = StagingLocation::with(['warehouse','area','zone'])->get();
        return new StagingLocationCollection($staging_locations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStagingLocationRequest $request): StagingLocationResource
    {
        $staging_location = $this->stagingLocationRepository->create($request->validated());
        return new StagingLocationResource($staging_location);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(StagingLocation $staging_location): StagingLocationResource
    {
        $staging_location = $this->stagingLocationRepository->get($staging_location);
        return new StagingLocationResource($staging_location);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStagingLocationRequest $request, StagingLocation $stagingLocation): StagingLocationResource
    {
        $update_staging_location = $this->stagingLocationRepository->update($stagingLocation, $request->validated());
        return new StagingLocationResource($update_staging_location);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StagingLocation $delete_staging_location)
    {
        $this->stagingLocationRepository->delete($delete_staging_location);
        return response()->json([
            'status' => true,
            'message' => 'Staging Location deleted successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\api\v1\warehouse;

use App\Models\Location;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\warehouse\LocationResource;
use App\Repositories\Admin\api\v1\warehouse\LocationRepository;
use App\Http\Resources\Admin\api\v1\warehouse\LocationCollection;
use App\Http\Requests\Admin\api\v1\warehouse\StoreLocationRequest;
use App\Http\Requests\Admin\api\v1\warehouse\UpdateLocationRequest;

class LocationController extends Controller
{
    protected $locationRepository;

    public function __construct(LocationRepository $locationRepository)
    {
        $this->locationRepository = $locationRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): LocationCollection
    {
        $locations = Location::with(['zone'])->get();
        return new LocationCollection($locations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLocationRequest $request): LocationResource
    {
        $location = $this->locationRepository->create($request->validated());
        return new LocationResource($location);
    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location): LocationResource
    {
        $location = $this->locationRepository->get($location);
        return new LocationResource($location->load('area'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLocationRequest $request, Location $location): LocationResource
    {
        $location = $this->locationRepository->update($location, $request->validated());
        return new LocationResource($location);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        $this->locationRepository->delete($location);
        return response()->json([
            'status' => true,
            'message' => 'location deleted successfully.',
        ]);
    }
}

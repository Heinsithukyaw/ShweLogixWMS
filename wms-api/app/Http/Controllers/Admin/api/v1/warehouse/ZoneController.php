<?php

namespace App\Http\Controllers\Admin\api\v1\warehouse;

use App\Models\Zone;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\warehouse\ZoneResource;
use App\Repositories\Admin\api\v1\warehouse\ZoneRepository;
use App\Http\Resources\Admin\api\v1\warehouse\ZoneCollection;
use App\Http\Requests\Admin\api\v1\warehouse\StoreZoneRequest;
use App\Http\Requests\Admin\api\v1\warehouse\UpdateZoneRequest;

class ZoneController extends Controller
{
    protected $zoneRepository;

    public function __construct(ZoneRepository $zoneRepository)
    {
        $this->zoneRepository = $zoneRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ZoneCollection
    {
        $zones = Zone::with(['area','locations'])->get();
        return new ZoneCollection($zones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreZoneRequest $request): ZoneResource
    {
        $zone = $this->zoneRepository->create($request->validated());
        return new ZoneResource($zone);
    }

    /**
     * Display the specified resource.
     */
    public function show(Zone $zone): ZoneResource
    {
        $zone = $this->zoneRepository->get($zone);
        return new ZoneResource($zone->load('area'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateZoneRequest $request, Zone $zone): ZoneResource
    {
        $zone = $this->zoneRepository->update($zone, $request->validated());
        return new ZoneResource($zone);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Zone $zone)
    {
        $this->zoneRepository->delete($zone);
        return response()->json([
            'status' => true,
            'message' => 'Zone deleted successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin\api\v1\geographical;

use App\Models\City;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\geographical\CityResource;
use App\Repositories\Admin\api\v1\geographical\CityRepository;
use App\Http\Resources\Admin\api\v1\geographical\CityCollection;
use App\Http\Requests\Admin\api\v1\geographical\StoreCityRequest;
use App\Http\Requests\Admin\api\v1\geographical\UpdateCityRequest;

class CityController extends Controller
{
    protected $cityRepository;

    public function __construct(CityRepository $cityRepository)
    {
        $this->cityRepository = $cityRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): CityCollection
    {
        $cities = City::with(['country','state'])->get();
        return new CityCollection($cities);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCityRequest $request): CityResource
    {
        $city = $this->cityRepository->create($request->validated());
        return new CityResource($city);
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city): CityResource
    {
        $city = $this->CityRepository->get($city);
        return new CityResource($city->load('country','state'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, City $city): CityResource
    {
        $update_city = $this->cityRepository->update($city, $request->validated());
        return new CityResource($update_city);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $delete_city)
    {
        $this->cityRepository->delete($delete_city);
        return response()->json([
            'status' => true,
            'message' => 'City deleted successfully.',
        ]);
    }
}

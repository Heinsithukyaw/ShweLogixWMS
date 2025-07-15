<?php

namespace App\Http\Controllers\Admin\api\v1\geographical;

use App\Models\Country;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\geographical\CountryResource;
use App\Repositories\Admin\api\v1\geographical\CountryRepository;
use App\Http\Resources\Admin\api\v1\geographical\CountryCollection;
use App\Http\Requests\Admin\api\v1\geographical\StoreCountryRequest;
use App\Http\Requests\Admin\api\v1\geographical\UpdateCountryRequest;

class CountryController extends Controller
{
    protected $countryRepository;

    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): CountryCollection
    {
        $countries = Country::with(['currency'])->get();
        return new CountryCollection($countries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request): CountryResource
    {
        $country = $this->countryRepository->create($request->validated());
        return new CountryResource($country);
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country): CountryResource
    {
        $country = $this->countryRepository->get($country);
        return new countryResource($country->load('currency'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, Country $country): CountryResource
    {
        $update_country = $this->countryRepository->update($country, $request->validated());
        return new CountryResource($update_country);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $delete_country)
    {
        $this->countryRepository->delete($delete_country);
        return response()->json([
            'status' => true,
            'message' => 'Country deleted successfully.',
        ]);
    }
}

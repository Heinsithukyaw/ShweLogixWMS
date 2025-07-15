<?php

namespace App\Http\Controllers\Admin\api\v1\business;

use App\Models\BusinessParty;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\business\BusinessPartyResource;
use App\Repositories\Admin\api\v1\business\BusinessPartyRepository;
use App\Http\Resources\Admin\api\v1\business\BusinessPartyCollection;
use App\Http\Requests\Admin\api\v1\business\StoreBusinessPartyRequest;
use App\Http\Requests\Admin\api\v1\business\UpdateBusinessPartyRequest;

class BusinessPartyController extends Controller
{
    protected $businessPartyRepository;

    public function __construct(BusinessPartyRepository $businessPartyRepository)
    {
        $this->businessPartyRepository = $businessPartyRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): BusinessPartyCollection
    {
        $parties = BusinessParty::all();
        return new BusinessPartyCollection($parties);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessPartyRequest $request): BusinessPartyResource
    {
        $party = $this->businessPartyRepository->create($request->validated());
        return new businessPartyResource($party);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessParty $party): BusinessPartyResource
    {
        $party = $this->businessPartyRepository->get($party);
        return new BusinessPartyResource();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessPartyRequest $request, BusinessParty $businessParty): BusinessPartyResource
    {
        $party = $this->businessPartyRepository->update($businessParty, $request->validated());
        return new BusinessPartyResource($party);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessParty $businessParty)
    {
        $this->businessPartyRepository->delete($businessParty);
        return response()->json([
            'status' => true,
            'message' => 'Business Party deleted successfully.',
        ]);
    }
}

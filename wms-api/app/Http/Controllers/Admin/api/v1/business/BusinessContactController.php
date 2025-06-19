<?php

namespace App\Http\Controllers\Admin\api\v1\business;

use App\Models\BusinessContact;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\business\BusinessContactResource;
use App\Repositories\Admin\api\v1\business\BusinessContactRepository;
use App\Http\Resources\Admin\api\v1\business\BusinessContactCollection;
use App\Http\Requests\Admin\api\v1\business\StoreBusinessContactRequest;
use App\Http\Requests\Admin\api\v1\business\UpdateBusinessContactRequest;

class BusinessContactController extends Controller
{
    protected $businessContactRepository;

    public function __construct(BusinessContactRepository $businessContactRepository)
    {
        $this->businessContactRepository = $businessContactRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): BusinessContactCollection
    {
        $parties = BusinessContact::with('business_party')->get();
        return new BusinessContactCollection($parties);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessContactRequest $request): BusinessContactResource
    {
        $contact = $this->businessContactRepository->create($request->validated());
        return new businessContactResource($contact);
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessContact $contact): BusinessContactResource
    {
        $contact = $this->businessContactRepository->get($contact);
        return new BusinessContactResource();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessContactRequest $request, BusinessContact $businessContact): BusinessContactResource
    {
        $contact = $this->businessContactRepository->update($businessContact, $request->validated());
        return new BusinessContactResource($contact);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessContact $businessContact)
    {
        logger($contact);
        $this->businessContactRepository->delete($businessContact);
        return response()->json([
            'status' => true,
            'message' => 'Business Contact deleted successfully.',
        ]);
    }
}

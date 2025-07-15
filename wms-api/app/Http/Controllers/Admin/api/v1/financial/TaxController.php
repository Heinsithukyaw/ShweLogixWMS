<?php

namespace App\Http\Controllers\Admin\api\v1\financial;

use App\Models\Tax;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\financial\TaxResource;
use App\Repositories\Admin\api\v1\financial\TaxRepository;
use App\Http\Resources\Admin\api\v1\financial\TaxCollection;
use App\Http\Requests\Admin\api\v1\financial\StoreTaxRequest;
use App\Http\Requests\Admin\api\v1\financial\UpdateTaxRequest;

class TaxController extends Controller
{
    protected $taxRepository;

    public function __construct(TaxRepository $taxRepository)
    {
        $this->taxRepository = $taxRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): TaxCollection
    {
        $taxes = Tax::all();
        return new TaxCollection($taxes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaxRequest $request): TaxResource
    {
        $tax = $this->taxRepository->create($request->validated());
        return new TaxResource($tax);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax): TaxResource
    {
        $tax = $this->taxRepository->get($tax);
        return new TaxResource($tax);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaxRequest $request, Tax $tax): TaxResource
    {
        $update_tax = $this->taxRepository->update($tax, $request->validated());
        return new TaxResource($update_tax);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax)
    {
        $this->taxRepository->delete($tax);
        return response()->json([
            'status' => true,
            'message' => 'Tax deleted successfully.',
        ]);
    }
}

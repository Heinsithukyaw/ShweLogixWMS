<?php

namespace App\Http\Controllers\Admin\api\v1\financial;

use App\Models\Currency;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\financial\CurrencyResource;
use App\Repositories\Admin\api\v1\financial\CurrencyRepository;
use App\Http\Resources\Admin\api\v1\financial\CurrencyCollection;
use App\Http\Requests\Admin\api\v1\financial\StoreCurrencyRequest;
use App\Http\Requests\Admin\api\v1\financial\UpdateCurrencyRequest;

class CurrencyController extends Controller
{
    protected $currencyRepository;

    public function __construct(CurrencyRepository $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): CurrencyCollection
    {
        $currencies = Currency::all();
        return new CurrencyCollection($currencies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCurrencyRequest $request): CurrencyResource
    {
        $currency = $this->currencyRepository->create($request->validated());
        return new CurrencyResource($currency);
    }

    /**
     * Display the specified resource.
     */
    public function show(Currency $currency): CurrencyResource
    {
        $currency = $this->currencyRepository->get($currency);
        return new CurrencyResource($currency);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency): CurrencyResource
    {
        $cur = $this->currencyRepository->update($currency, $request->validated());
        return new CurrencyResource($cur);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Currency $currency)
    {
        $this->currencyRepository->delete($currency);
        return response()->json([
            'status' => true,
            'message' => 'Currency deleted successfully.',
        ]);
    }
}

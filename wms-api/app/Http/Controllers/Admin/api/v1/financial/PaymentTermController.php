<?php

namespace App\Http\Controllers\Admin\api\v1\financial;

use App\Models\PaymentTerm;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\financial\PaymentTermResource;
use App\Repositories\Admin\api\v1\financial\PaymentTermRepository;
use App\Http\Resources\Admin\api\v1\financial\PaymentTermCollection;
use App\Http\Requests\Admin\api\v1\financial\StorePaymentTermRequest;
use App\Http\Requests\Admin\api\v1\financial\UpdatePaymentTermRequest;

class PaymentTermController extends Controller
{
    protected $paymentTermRepository;

    public function __construct(PaymentTermRepository $paymentTermRepository)
    {
        $this->paymentTermRepository = $paymentTermRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): PaymentTermCollection
    {
        $payment_terms = PaymentTerm::all();
        return new PaymentTermCollection($payment_terms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentTermRequest $request): PaymentTermResource
    {
        $payment_term = $this->paymentTermRepository->create($request->validated());
        return new PaymentTermResource($payment_term);
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentTerm $payment_term): PaymentTermResource
    {
        $payment_term = $this->paymentTermRepository->get($payment_term);
        return new PaymentTermResource($payment_term);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentTermRequest $request, PaymentTerm $paymentTerm): PaymentTermResource
    {
        $payment_term = $this->paymentTermRepository->update($paymentTerm, $request->validated());
        return new PaymentTermResource($payment_term);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentTerm $paymentTerm)
    {
        $this->paymentTermRepository->delete($paymentTerm);
        return response()->json([
            'status' => true,
            'message' => 'Payment Term deleted successfully.',
        ]);
    }
}

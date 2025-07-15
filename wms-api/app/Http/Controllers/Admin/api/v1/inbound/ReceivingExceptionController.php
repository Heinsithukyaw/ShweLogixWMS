<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\ReceivingException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingExceptionResource;
use App\Repositories\Admin\api\v1\inbound\ReceivingExceptionRepository;
use App\Http\Resources\Admin\api\v1\inbound\ReceivingExceptionCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreReceivingExceptionRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateReceivingExceptionRequest;

class ReceivingExceptionController extends Controller
{
    protected $receivingExceptionRepository;

    public function __construct(ReceivingExceptionRepository $receivingExceptionRepository)
    {
        $this->receivingExceptionRepository = $receivingExceptionRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): ReceivingExceptionCollection
    {
        $receiving_exceptions = ReceivingException::with(['asn','asn_detail','reported_emp','assigned_emp','product'])->get();
        return new ReceivingExceptionCollection($receiving_exceptions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReceivingExceptionRequest $request): ReceivingExceptionResource
    {
        $receiving_exception = $this->receivingExceptionRepository->create($request->validated());
        return new ReceivingExceptionResource($receiving_exception);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(ReceivingException $receiving_exception): ReceivingExceptionResource
    {
        $receiving_exception = $this->receivingExceptionRepository->get($receiving_exception);
        return new ReceivingExceptionResource($receiving_exception);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReceivingExceptionRequest $request, ReceivingException $receivingException): ReceivingExceptionResource
    {
        $update_receiving_exception = $this->receivingExceptionRepository->update($receivingException, $request->validated());
        return new ReceivingExceptionResource($update_receiving_exception);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReceivingException $delete_receiving_exception)
    {
        $this->receivingExceptionRepository->delete($delete_receiving_exception);
        return response()->json([
            'status' => true,
            'message' => 'Receiving Exception deleted successfully.',
        ]);
    }
}

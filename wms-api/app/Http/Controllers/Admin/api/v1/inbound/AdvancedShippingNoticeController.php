<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\AdvancedShippingNotice;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\AdvancedShippingNoticeResource;
use App\Repositories\Admin\api\v1\inbound\AdvancedShippingNoticeRepository;
use App\Http\Resources\Admin\api\v1\inbound\AdvancedShippingNoticeCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreAdvancedShippingNoticeRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateAdvancedShippingNoticeRequest;
use App\Services\EventService;

class AdvancedShippingNoticeController extends Controller
{
    protected $advancedShippingNoticeRepository;

    public function __construct(AdvancedShippingNoticeRepository $advancedShippingNoticeRepository)
    {
        $this->advancedShippingNoticeRepository = $advancedShippingNoticeRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AdvancedShippingNoticeCollection
    {
        $asn_lists = AdvancedShippingNotice::with(['supplier','carrier'])->orderBy('id','desc')->get();
        return new AdvancedShippingNoticeCollection($asn_lists);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdvancedShippingNoticeRequest $request): AdvancedShippingNoticeResource
    {
        $asn = $this->advancedShippingNoticeRepository->create($request->validated());
        
        // Dispatch ASN received event
        EventService::asnReceived($asn);
        
        return new AdvancedShippingNoticeResource($asn);
    }

    /**
     * Display the specified resource.
     */
    public function show(AdvancedShippingNotice $advancedShippingNotice): AdvancedShippingNoticeResource
    {
        $asn = $this->advancedShippingNoticeRepository->get($advancedShippingNotice);
        return new AdvancedShippingNoticeResource($asn);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdvancedShippingNoticeRequest $request, AdvancedShippingNotice $advancedShippingNotice): AdvancedShippingNoticeResource
    {
        $asn = $this->advancedShippingNoticeRepository->update($advancedShippingNotice, $request->validated());
        return new AdvancedShippingNoticeResource($asn);
    }

    public function destroy(AdvancedShippingNotice $advancedShippingNotice)
    {
        $this->advancedShippingNoticeRepository->delete($advancedShippingNotice);
        return response()->json([
            'status' => true,
            'message' => 'Advanced Shipping Notice deleted successfully.',
        ]);
    }

}

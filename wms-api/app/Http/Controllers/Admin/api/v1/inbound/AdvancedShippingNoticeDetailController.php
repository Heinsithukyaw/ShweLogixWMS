<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\AdvancedShippingNoticeDetail;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\AdvancedShippingNoticeDetailResource;
use App\Repositories\Admin\api\v1\inbound\AdvancedShippingNoticeDetailRepository;
use App\Http\Resources\Admin\api\v1\inbound\AdvancedShippingNoticeDetailCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreAdvancedShippingNoticeDetailRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateAdvancedShippingNoticeDetailRequest;

class AdvancedShippingNoticeDetailController extends Controller
{
    protected $advancedShippingNoticeDetailRepository;

    public function __construct(AdvancedShippingNoticeDetailRepository $advancedShippingNoticeDetailRepository)
    {
        $this->advancedShippingNoticeDetailRepository = $advancedShippingNoticeDetailRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AdvancedShippingNoticeDetailCollection
    {
        $asn_detail_lists = AdvancedShippingNoticeDetail::with(['advanced_shipping_notice','product','unit_of_measure','zoneLocation','pallet_equipment'])->get();
        return new AdvancedShippingNoticeDetailCollection($asn_detail_lists);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdvancedShippingNoticeDetailRequest $request): AdvancedShippingNoticeDetailResource
    {
        $asn_detail = $this->advancedShippingNoticeDetailRepository->create($request->validated());
        return new AdvancedShippingNoticeDetailResource($asn_detail);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(AdvancedShippingNoticeDetail $asn_detail): AdvancedShippingNoticeDetailResource
    {
        $asn_detail = $this->advancedShippingNoticeDetailRepository->get($asn_detail);
        return new AdvancedShippingNoticeDetailResource($asn_detail);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdvancedShippingNoticeDetailRequest $request, AdvancedShippingNoticeDetail $advancedShippingNoticeDetail): AdvancedShippingNoticeDetailResource
    {
        $asnDetail = $this->advancedShippingNoticeDetailRepository->update($advancedShippingNoticeDetail, $request->validated());
        return new AdvancedShippingNoticeDetailResource($asnDetail);
    }

    public function destroy(AdvancedShippingNoticeDetail $advancedShippingNoticeDetail)
    {
        $this->advancedShippingNoticeDetailRepository->delete($advancedShippingNoticeDetail);
        return response()->json([
            'status' => true,
            'message' => 'Advanced Shipping Notice Detail deleted successfully.',
        ]);
    }
}

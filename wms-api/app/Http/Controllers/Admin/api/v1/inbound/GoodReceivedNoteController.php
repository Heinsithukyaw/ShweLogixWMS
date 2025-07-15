<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\GoodReceivedNote;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\api\v1\inbound\GoodReceivedNoteResource;
use App\Repositories\Admin\api\v1\inbound\GoodReceivedNoteRepository;
use App\Http\Resources\Admin\api\v1\inbound\GoodReceivedNoteCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreGoodReceivedNoteRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateGoodReceivedNoteRequest;
use App\Services\EventService;

class GoodReceivedNoteController extends Controller
{
    protected $goodReceivedNoteRepository;

    public function __construct(GoodReceivedNoteRepository $goodReceivedNoteRepository)
    {
        $this->goodReceivedNoteRepository = $goodReceivedNoteRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): GoodReceivedNoteCollection
    {
        $grn_lists = GoodReceivedNote::with(['inbound_shipment','supplier','created_emp','approved_emp'])->get();
        return new GoodReceivedNoteCollection($grn_lists);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGoodReceivedNoteRequest $request): GoodReceivedNoteResource
    {
        $grn = $this->goodReceivedNoteRepository->create($request->validated());
        
        // Dispatch goods received event
        EventService::goodsReceived($grn);
        
        return new GoodReceivedNoteResource($grn);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(GoodReceivedNote $goodReceivedNote): GoodReceivedNoteResource
    {
        $grn = $this->goodReceivedNoteRepository->get($goodReceivedNote);
        return new GoodReceivedNoteResource($grn);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGoodReceivedNoteRequest $request, GoodReceivedNote $goodReceivedNote): GoodReceivedNoteResource
    {
        $grn = $this->goodReceivedNoteRepository->update($goodReceivedNote, $request->validated());
        
        // If the GRN status is changed to approved or completed, dispatch goods received event
        if (isset($request->validated()['status']) && in_array($request->validated()['status'], ['approved', 'completed'])) {
            EventService::goodsReceived($grn);
        }
        
        return new GoodReceivedNoteResource($grn);
    }

    public function destroy(GoodReceivedNote $goodReceivedNote)
    {
        $this->goodReceivedNoteRepository->delete($goodReceivedNote);
        return response()->json([
            'status' => true,
            'message' => 'Good Received Note deleted successfully.',
        ]);
    }
}

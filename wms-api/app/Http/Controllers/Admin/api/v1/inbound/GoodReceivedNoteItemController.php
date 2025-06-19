<?php

namespace App\Http\Controllers\Admin\api\v1\inbound;

use App\Models\GoodReceivedNoteItem;
use App\Models\GoodReceivedNote;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Admin\api\v1\inbound\GoodReceivedNoteItemResource;
use App\Repositories\Admin\api\v1\inbound\GoodReceivedNoteItemRepository;
use App\Http\Resources\Admin\api\v1\inbound\GoodReceivedNoteItemCollection;
use App\Http\Requests\Admin\api\v1\inbound\StoreGoodReceivedNoteItemRequest;
use App\Http\Requests\Admin\api\v1\inbound\UpdateGoodReceivedNoteItemRequest;

class GoodReceivedNoteItemController extends Controller
{
    protected $goodReceivedNoteItemRepository;

    public function __construct(GoodReceivedNoteItemRepository $goodReceivedNoteItemRepository)
    {
        $this->goodReceivedNoteItemRepository = $goodReceivedNoteItemRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): GoodReceivedNoteItemCollection
    {
        $grn_item_lists = GoodReceivedNoteItem::with(['good_received_note','product','unit_of_measure','stagingLocation'])->get();
        return new GoodReceivedNoteItemCollection($grn_item_lists);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        logger($request->all());
        $grn = GoodReceivedNote::latest()->first();
        foreach ($request->items as $item) {
            GoodReceivedNoteItem::create([
                'grn_id' => $grn?->id,
                'product_id' => $item['product_id'],
                'uom_id' => $item['uom_id'],
                'location_id' => $item['location_id'],
                'condition_status' => $item['condition_status'],
                'expected_qty' => $item['expected_qty'],
                'received_qty' => $item['received_qty'],
                'notes' => $item['notes'],
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Good Received Note Item created successfully.',
        ]);
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(GoodReceivedNoteItem $goodReceivedNoteItem): GoodReceivedNoteItemResource
    {
        $grn_item = $this->goodReceivedNoteItemRepository->get($goodReceivedNoteItem);
        return new GoodReceivedNoteItemResource($grn_item);
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'nullable',
            'items.*.grn_id' => 'required|integer',
            'items.*.product_id' => 'required|integer',
            'items.*.uom_id' => 'required|integer',
            'items.*.expected_qty' => 'nullable',
            'items.*.received_qty' => 'nullable',
            'items.*.location_id' => 'required|integer',
            'items.*.condition_status' => 'required|integer',
            'items.*.notes' => 'nullable',
        ]);

        $items = $validated['items'];

        $incomingItemIds = collect($items)->pluck('id')->filter()->all();
        logger($incomingItemIds);
        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                logger($item);
                if (!empty($item['id'])) {
                    // Update existing item
                    logger('update include');
                    GoodReceivedNoteItem::where('id', $item['id'])->update([
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'expected_qty' => $item['expected_qty'],
                        'received_qty' => $item['received_qty'],
                        'location_id' => $item['location_id'],
                        'condition_status' => $item['condition_status'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                    GoodReceivedNoteItem::where('grn_id', $item['grn_id'])
                    ->whereNotIn('id', $incomingItemIds)
                    ->delete();
                    logger('delete');
                } else {
                    // Create new item
                    logger('create include');
                    GoodReceivedNoteItem::create([
                        'grn_id' => $item['grn_id'],
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'expected_qty' => $item['expected_qty'],
                        'received_qty' => $item['received_qty'],
                        'location_id' => $item['location_id'],
                        'condition_status' => $item['condition_status'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Delete items that were removed in the frontend


            DB::commit();
            return response()->json(['status' => true, 'message' => 'GRN Items updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function destroy(GoodReceivedNoteItem $goodReceivedNoteItem)
    {
        $this->goodReceivedNoteItemRepository->delete($goodReceivedNoteItem);
        return response()->json([
            'status' => true,
            'message' => 'Good Received Note Item deleted successfully.',
        ]);
    }
}

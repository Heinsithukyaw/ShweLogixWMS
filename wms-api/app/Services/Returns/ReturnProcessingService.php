<?php

namespace App\Services\Returns;

use App\Models\Returns\ReturnAuthorization;
use App\Models\Returns\ReturnReceipt;
use App\Models\Returns\ReturnReceiptItem;
use App\Models\Returns\ReverseLogisticsOrder;
use App\Models\Returns\RefurbishmentTask;
use App\Models\ProductInventory;
use App\Traits\UsesTransactionalEvents;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReturnProcessingService
{
    use UsesTransactionalEvents;

    /**
     * Process return receipt and determine disposition
     */
    public function processReturnReceipt(ReturnReceipt $receipt, array $itemDispositions): array
    {
        DB::beginTransaction();
        
        try {
            $processedItems = [];
            $inventoryUpdates = [];
            $refurbishmentTasks = [];
            $reverseLogisticsItems = [];

            foreach ($itemDispositions as $itemData) {
                $receiptItem = ReturnReceiptItem::find($itemData['receipt_item_id']);
                
                if (!$receiptItem) {
                    continue;
                }

                // Update receipt item with disposition
                $receiptItem->update([
                    'disposition' => $itemData['disposition'],
                    'condition' => $itemData['condition'],
                    'inspection_notes' => $itemData['inspection_notes'] ?? null,
                    'restocking_fee' => $itemData['restocking_fee'] ?? 0,
                    'refund_amount' => $itemData['refund_amount'] ?? 0
                ]);

                // Process based on disposition
                switch ($itemData['disposition']) {
                    case 'restock':
                        $inventoryUpdates[] = $this->processRestock($receiptItem);
                        break;
                        
                    case 'refurbish':
                        $refurbishmentTasks[] = $this->createRefurbishmentTask($receiptItem, $itemData);
                        break;
                        
                    case 'scrap':
                    case 'return_to_vendor':
                    case 'donate':
                        $reverseLogisticsItems[] = $this->createReverseLogisticsItem($receiptItem, $itemData);
                        break;
                }

                $processedItems[] = $receiptItem;
            }

            // Update inventory for restocked items
            foreach ($inventoryUpdates as $update) {
                $this->updateInventory($update);
            }

            // Create reverse logistics order if needed
            if (!empty($reverseLogisticsItems)) {
                $this->createReverseLogisticsOrder($receipt, $reverseLogisticsItems);
            }

            // Fire processing event
            $this->fireTransactionalEvent('returns.receipt.processed', [
                'receipt_id' => $receipt->id,
                'return_authorization_id' => $receipt->return_authorization_id,
                'processed_items_count' => count($processedItems),
                'restocked_items' => count($inventoryUpdates),
                'refurbishment_tasks' => count($refurbishmentTasks),
                'reverse_logistics_items' => count($reverseLogisticsItems)
            ]);

            DB::commit();

            return [
                'success' => true,
                'processed_items' => count($processedItems),
                'inventory_updates' => count($inventoryUpdates),
                'refurbishment_tasks' => count($refurbishmentTasks),
                'reverse_logistics_items' => count($reverseLogisticsItems)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process restock disposition
     */
    private function processRestock(ReturnReceiptItem $item): array
    {
        return [
            'product_id' => $item->product_id,
            'warehouse_id' => $item->returnReceipt->warehouse_id,
            'location_id' => $item->returnReceipt->location_id,
            'quantity' => $item->received_quantity,
            'condition' => $item->condition,
            'serial_number' => $item->serial_number,
            'batch_number' => $item->batch_number,
            'reference_type' => 'return_receipt',
            'reference_id' => $item->id
        ];
    }

    /**
     * Create refurbishment task
     */
    private function createRefurbishmentTask(ReturnReceiptItem $item, array $itemData): RefurbishmentTask
    {
        $taskNumber = 'REF-' . date('Y') . '-' . str_pad(
            RefurbishmentTask::whereYear('created_at', date('Y'))->count() + 1,
            6,
            '0',
            STR_PAD_LEFT
        );

        return RefurbishmentTask::create([
            'task_number' => $taskNumber,
            'return_receipt_item_id' => $item->id,
            'product_id' => $item->product_id,
            'status' => 'pending',
            'priority' => $this->determineRefurbishmentPriority($item),
            'warehouse_id' => $item->returnReceipt->warehouse_id,
            'work_description' => $this->generateRefurbishmentDescription($item, $itemData),
            'estimated_cost' => $itemData['estimated_refurbishment_cost'] ?? 0,
            'estimated_hours' => $itemData['estimated_refurbishment_hours'] ?? 2,
            'required_parts' => $itemData['required_parts'] ?? [],
            'scheduled_date' => $this->calculateRefurbishmentSchedule($item)
        ]);
    }

    /**
     * Create reverse logistics item
     */
    private function createReverseLogisticsItem(ReturnReceiptItem $item, array $itemData): array
    {
        return [
            'return_receipt_item_id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->received_quantity,
            'condition' => $item->condition,
            'disposition' => $itemData['disposition'],
            'unit_cost' => $itemData['disposal_cost'] ?? 0,
            'total_cost' => ($itemData['disposal_cost'] ?? 0) * $item->received_quantity,
            'notes' => $itemData['disposal_notes'] ?? null,
            'serial_number' => $item->serial_number,
            'batch_number' => $item->batch_number
        ];
    }

    /**
     * Update inventory for restocked items
     */
    private function updateInventory(array $update): void
    {
        $inventory = ProductInventory::firstOrCreate([
            'product_id' => $update['product_id'],
            'warehouse_id' => $update['warehouse_id'],
            'location_id' => $update['location_id']
        ], [
            'quantity_on_hand' => 0,
            'quantity_available' => 0,
            'quantity_reserved' => 0
        ]);

        // Adjust quantities based on condition
        $quantityAdjustment = $update['quantity'];
        
        if ($update['condition'] === 'damaged' || $update['condition'] === 'defective') {
            // Add to quarantine or damaged stock
            $inventory->increment('quantity_damaged', $quantityAdjustment);
        } else {
            // Add to available stock
            $inventory->increment('quantity_on_hand', $quantityAdjustment);
            $inventory->increment('quantity_available', $quantityAdjustment);
        }

        // Create inventory transaction record
        $this->createInventoryTransaction($update, $quantityAdjustment);
    }

    /**
     * Create reverse logistics order
     */
    private function createReverseLogisticsOrder(ReturnReceipt $receipt, array $items): ReverseLogisticsOrder
    {
        // Group items by disposition type
        $groupedItems = collect($items)->groupBy('disposition');
        
        foreach ($groupedItems as $disposition => $dispositionItems) {
            $orderNumber = 'RLO-' . strtoupper(substr($disposition, 0, 3)) . '-' . date('Y') . '-' . 
                str_pad(ReverseLogisticsOrder::whereYear('created_at', date('Y'))->count() + 1, 4, '0', STR_PAD_LEFT);

            $order = ReverseLogisticsOrder::create([
                'order_number' => $orderNumber,
                'type' => $disposition,
                'status' => 'pending',
                'warehouse_id' => $receipt->warehouse_id,
                'created_by' => auth()->id(),
                'description' => "Reverse logistics order for {$disposition} items from return receipt {$receipt->receipt_number}",
                'estimated_cost' => $dispositionItems->sum('total_cost'),
                'scheduled_date' => $this->calculateReverseLogisticsSchedule($disposition)
            ]);

            // Create order items
            foreach ($dispositionItems as $item) {
                $order->items()->create($item);
            }
        }

        return $order;
    }

    /**
     * Calculate return processing metrics
     */
    public function calculateProcessingMetrics(ReturnAuthorization $authorization): array
    {
        $receipts = $authorization->receipts()->with('items')->get();
        
        $metrics = [
            'total_items_requested' => $authorization->items()->sum('requested_quantity'),
            'total_items_approved' => $authorization->items()->sum('approved_quantity'),
            'total_items_received' => $receipts->sum(function ($receipt) {
                return $receipt->items->sum('received_quantity');
            }),
            'processing_time_days' => $authorization->processed_date 
                ? $authorization->requested_date->diffInDays($authorization->processed_date)
                : null,
            'disposition_breakdown' => $this->getDispositionBreakdown($receipts),
            'financial_impact' => $this->calculateFinancialImpact($authorization, $receipts),
            'recovery_rate' => $this->calculateRecoveryRate($receipts)
        ];

        return $metrics;
    }

    /**
     * Get disposition breakdown
     */
    private function getDispositionBreakdown($receipts): array
    {
        $breakdown = [];
        
        foreach ($receipts as $receipt) {
            foreach ($receipt->items as $item) {
                $disposition = $item->disposition ?? 'pending';
                $breakdown[$disposition] = ($breakdown[$disposition] ?? 0) + $item->received_quantity;
            }
        }

        return $breakdown;
    }

    /**
     * Calculate financial impact
     */
    private function calculateFinancialImpact(ReturnAuthorization $authorization, $receipts): array
    {
        $totalRefunds = $receipts->sum(function ($receipt) {
            return $receipt->items->sum('refund_amount');
        });

        $totalRestockingFees = $receipts->sum(function ($receipt) {
            return $receipt->items->sum('restocking_fee');
        });

        $processingCosts = $this->calculateProcessingCosts($receipts);

        return [
            'total_refunds' => $totalRefunds,
            'total_restocking_fees' => $totalRestockingFees,
            'processing_costs' => $processingCosts,
            'net_impact' => $totalRefunds + $processingCosts - $totalRestockingFees,
            'original_value' => $authorization->estimated_value
        ];
    }

    /**
     * Calculate recovery rate
     */
    private function calculateRecoveryRate($receipts): float
    {
        $totalItems = $receipts->sum(function ($receipt) {
            return $receipt->items->sum('received_quantity');
        });

        $recoveredItems = $receipts->sum(function ($receipt) {
            return $receipt->items->where('disposition', 'restock')->sum('received_quantity');
        });

        return $totalItems > 0 ? ($recoveredItems / $totalItems) * 100 : 0;
    }

    /**
     * Helper methods
     */
    private function determineRefurbishmentPriority(ReturnReceiptItem $item): string
    {
        // Logic to determine priority based on product value, condition, etc.
        return 'medium';
    }

    private function generateRefurbishmentDescription(ReturnReceiptItem $item, array $itemData): string
    {
        return "Refurbish {$item->product->name} - Condition: {$item->condition}. " . 
               ($itemData['refurbishment_notes'] ?? 'Standard refurbishment process.');
    }

    private function calculateRefurbishmentSchedule(ReturnReceiptItem $item): Carbon
    {
        // Schedule based on priority and workload
        return now()->addDays(3);
    }

    private function calculateReverseLogisticsSchedule(string $disposition): Carbon
    {
        $scheduleMap = [
            'scrap' => 7,
            'return_to_vendor' => 5,
            'donate' => 10
        ];

        return now()->addDays($scheduleMap[$disposition] ?? 7);
    }

    private function createInventoryTransaction(array $update, int $quantity): void
    {
        // Create inventory transaction record
        // This would integrate with your inventory transaction system
    }

    private function calculateProcessingCosts($receipts): float
    {
        // Calculate labor costs, overhead, etc. for processing returns
        return $receipts->count() * 25.00; // Example: $25 per receipt processing cost
    }
}
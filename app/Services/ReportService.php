<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrintShop;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Build the Standardized Inventory & Stock Movement Report scoped strictly to a PrintShop.
     *
     * @return array<string, mixed>
     */
    public function buildInventoryReport(PrintShop $shop, string $period = 'this_month'): array
    {
        /** @var Collection<int, InventoryItem> $inventoryItems */
        $inventoryItems = $shop->inventoryItems()->orderBy('name')->get();
        $itemIds = $inventoryItems->pluck('id');

        $items = $inventoryItems->map(function (InventoryItem $item): array {
            $stockValue = round((float) $item->stock_qty * (float) $item->unit_cost, 2);
            $status = $item->isOutOfStock()
                ? 'Out of Stock'
                : ($item->isLowStock() ? 'Low Stock' : 'Healthy');

            return [
                'id' => $item->id,
                'sku' => $item->sku ?: 'N/A',
                'name' => $item->name,
                'category' => $item->category,
                'item_type' => $item->item_type,
                'unit' => $item->unit,
                'stock_qty' => round((float) $item->stock_qty, 2),
                'reserved_qty' => round((float) $item->reserved_qty, 2),
                'available_qty' => $item->availableQty(),
                'reorder_level' => round((float) $item->reorder_level, 2),
                'unit_cost' => round((float) $item->unit_cost, 2),
                'stock_value' => $stockValue,
                'status' => $status,
            ];
        })->values()->all();

        $movementQuery = StockMovement::query()
            ->whereIn('inventory_item_id', $itemIds)
            ->with(['inventoryItem', 'user'])
            ->latest('created_at');

        if ($period !== 'all_time' && $period !== 'all') {
            [$startDate, $endDate] = $this->resolveDateRange($period);
            $movementQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        /** @var Collection<int, StockMovement> $movementModels */
        $movementModels = $movementQuery->take(200)->get();

        $movements = $movementModels->map(fn (StockMovement $movement): array => [
            'id' => $movement->id,
            'created_at' => $movement->created_at?->format('M d, Y h:i A') ?? '',
            'item_name' => $movement->inventoryItem->name ?? 'Removed Item',
            'unit' => $movement->inventoryItem->unit ?? 'units',
            'movement_type' => $movement->movement_type,
            'quantity' => round((float) $movement->quantity, 2),
            'previous_stock' => round((float) $movement->previous_stock, 2),
            'resulting_stock' => round((float) $movement->resulting_stock, 2),
            'order_item_id' => $movement->order_item_id,
            'reference_note' => $movement->reference_note ?? '',
            'actor_name' => $movement->user->name ?? 'System',
        ])->values()->all();

        $spoilageCost = $movementModels
            ->whereIn('movement_type', [StockMovement::TYPE_SPOILAGE_WASTE, StockMovement::TYPE_REWORK_SPOILAGE])
            ->sum(fn (StockMovement $movement): float => abs((float) $movement->quantity) * (float) ($movement->inventoryItem->unit_cost ?? 0.0));

        return [
            'report_type' => 'inventory',
            'title' => 'Inventory & Stock Movement Report',
            'period' => $period,
            'generated_at' => now(),
            'summary' => [
                'total_skus' => $inventoryItems->count(),
                'low_stock_count' => $inventoryItems->filter(fn (InventoryItem $i): bool => $i->isLowStock())->count(),
                'out_of_stock_count' => $inventoryItems->filter(fn (InventoryItem $i): bool => $i->isOutOfStock())->count(),
                'total_valuation' => round((float) $inventoryItems->sum(fn (InventoryItem $i): float => (float) $i->stock_qty * (float) $i->unit_cost), 2),
                'rework_spoilage_count' => $movementModels->where('movement_type', StockMovement::TYPE_REWORK_SPOILAGE)->count(),
                'waste_spoilage_count' => $movementModels->where('movement_type', StockMovement::TYPE_SPOILAGE_WASTE)->count(),
                'spoilage_loss_cost' => round((float) $spoilageCost, 2),
            ],
            'items' => $items,
            'movements' => $movements,
        ];
    }

    /**
     * Build the Standardized Sales & Revenue Reconciliation Report scoped strictly to a PrintShop.
     *
     * @return array<string, mixed>
     */
    public function buildSalesReport(PrintShop $shop, string $period = 'this_month'): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($period);

        $orderQuery = Order::query()
            ->where('print_shop_id', $shop->id)
            ->with(['customer', 'items', 'payments'])
            ->latest('created_at');

        if ($period !== 'all_time' && $period !== 'all') {
            $orderQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        /** @var Collection<int, Order> $orders */
        $orders = $orderQuery->get();

        $paymentQuery = Payment::query()
            ->where('print_shop_id', $shop->id)
            ->where('status', Payment::STATUS_VERIFIED)
            ->with(['order.customer', 'verifiedBy'])
            ->latest('verified_at')
            ->latest('id');

        if ($period !== 'all_time' && $period !== 'all') {
            $paymentQuery->where(function (Builder $query) use ($startDate, $endDate): void {
                $query->whereBetween('verified_at', [$startDate, $endDate])
                    ->orWhere(function (Builder $nested) use ($startDate, $endDate): void {
                        $nested->whereNull('verified_at')->whereBetween('created_at', [$startDate, $endDate]);
                    });
            });
        }

        /** @var Collection<int, Payment> $verifiedPayments */
        $verifiedPayments = $paymentQuery->get();

        $validOrders = $orders->filter(
            fn (Order $order): bool => $order->order_status !== Order::STATUS_CANCELLED
                && $order->payment_status !== Order::PAYMENT_REJECTED
        );

        $downpayments50 = (float) $verifiedPayments->where('payment_type', Payment::TYPE_DOWNPAYMENT_50)->sum('amount');
        $fullPrepayments100 = (float) $verifiedPayments->where('payment_type', Payment::TYPE_FULL_100)->sum('amount');
        $counterSettlements = (float) $verifiedPayments->whereIn('payment_type', [
            Payment::TYPE_BALANCE_SETTLEMENT,
            Payment::TYPE_OFFLINE_COUNTER,
            Payment::TYPE_UPGRADE_TOPUP,
            Payment::TYPE_UPFRONT,
        ])->sum('amount');
        $manualRefundsSettled = (float) $verifiedPayments
            ->where('payment_type', Payment::TYPE_MANUAL_REFUND)
            ->sum(fn (Payment $payment): float => abs((float) $payment->amount));
        $netCashCollected = (float) $verifiedPayments->sum('amount');

        $shopItemIds = $shop->inventoryItems()->pluck('id');
        $spoilageQuery = StockMovement::query()
            ->whereIn('inventory_item_id', $shopItemIds)
            ->whereIn('movement_type', [StockMovement::TYPE_SPOILAGE_WASTE, StockMovement::TYPE_REWORK_SPOILAGE])
            ->with('inventoryItem');

        if ($period !== 'all_time' && $period !== 'all') {
            $spoilageQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $spoilageLoss = (float) $spoilageQuery->get()->sum(
            fn (StockMovement $movement): float => abs((float) $movement->quantity) * (float) ($movement->inventoryItem->unit_cost ?? 0.0)
        );

        $orderRows = $orders->map(fn (Order $order): array => [
            'order_number' => $order->order_number,
            'tracking_code' => $order->tracking_code ?? '—',
            'customer_name' => $order->customer->name ?? 'Guest',
            'service_label' => $order->displayServiceLabel(),
            'order_status' => ucwords(str_replace('_', ' ', $order->order_status)),
            'payment_status' => $order->paymentStatusLabel(),
            'total_amount' => round((float) $order->total_amount, 2),
            'amount_paid' => round((float) $order->amount_paid, 2),
            'balance_due' => round((float) $order->balance_due, 2),
            'refund_due' => round((float) $order->refund_due, 2),
            'refund_status' => $order->refund_status,
            'created_at' => $order->created_at?->format('M d, Y h:i A') ?? '',
        ])->values()->all();

        $paymentRows = $verifiedPayments->map(fn (Payment $payment): array => [
            'id' => $payment->id,
            'order_number' => $payment->order->order_number ?? '—',
            'customer_name' => $payment->order->customer->name ?? 'Customer',
            'payment_type' => $payment->payment_type,
            'payment_method' => $payment->payment_method,
            'reference_no' => $payment->reference_no ?? 'Counter',
            'amount' => round((float) $payment->amount, 2),
            'verified_by' => $payment->verifiedBy->name ?? 'Staff',
            'verified_at' => ($payment->verified_at ?? $payment->created_at)?->format('M d, Y h:i A') ?? '',
        ])->values()->all();

        return [
            'report_type' => 'sales',
            'title' => 'Sales & Revenue Reconciliation Report',
            'period' => $period,
            'generated_at' => now(),
            'summary' => [
                'orders_count' => $orders->count(),
                'gross_billed' => round((float) $validOrders->sum('total_amount'), 2),
                'downpayments_50' => round($downpayments50, 2),
                'full_prepayments_100' => round($fullPrepayments100, 2),
                'counter_balance_settlements' => round($counterSettlements, 2),
                'manual_refunds_settled' => round($manualRefundsSettled, 2),
                'net_cash_collected' => round($netCashCollected, 2),
                'outstanding_receivables' => round((float) $validOrders->sum('balance_due'), 2),
                'pending_manual_refunds' => round((float) $validOrders->where('refund_status', Order::REFUND_MANUAL_PENDING)->sum('refund_due'), 2),
                'spoilage_rework_loss' => round($spoilageLoss, 2),
            ],
            'orders' => $orderRows,
            'payments' => $paymentRows,
        ];
    }

    /**
     * Build the Live Production Schedule & Job Status Report scoped strictly to a PrintShop.
     *
     * @return array<string, mixed>
     */
    public function buildProductionScheduleReport(PrintShop $shop): array
    {
        /** @var Collection<int, OrderItem> $activeItems */
        $activeItems = OrderItem::query()
            ->whereHas('order', function (Builder $query) use ($shop): void {
                $query->where('print_shop_id', $shop->id)
                    ->whereNotIn('order_status', [
                        Order::STATUS_CANCELLED,
                        Order::STATUS_COMPLETED,
                        Order::STATUS_PENDING_PAYMENT,
                    ])
                    ->where('payment_status', '!=', Order::PAYMENT_REJECTED);
            })
            ->with(['order.customer', 'printer', 'assignedStaff'])
            ->orderBy('id')
            ->get();

        $mapRow = function (OrderItem $item): array {
            $readyAt = $item->ready_at;
            $agingDays = $readyAt !== null ? (int) $readyAt->diffInDays(now()) : 0;

            return [
                'item_id' => $item->id,
                'order_number' => $item->order->order_number ?? '—',
                'tracking_code' => $item->order->tracking_code ?? '—',
                'customer_name' => $item->order->customer->name ?? 'Customer',
                'service_label' => $item->isThesisBinding() ? 'Thesis Binding' : 'Document Printing',
                'copies_count' => $item->copies_count,
                'paper_size' => $item->paper_size,
                'stage' => $item->production_stage ?? OrderItem::STAGE_QUEUE,
                'stage_label' => $item->stageLabel(),
                'printer_name' => $item->printer->name ?? 'Unassigned',
                'staff_name' => $item->assignedStaff->name ?? 'Unassigned',
                'shelf_bin_code' => $item->shelf_bin_code ?? '—',
                'ready_at' => $readyAt?->format('M d, Y h:i A') ?? '—',
                'aging_days' => $agingDays,
                'is_aging' => $item->isUnclaimedAging(3),
                'balance_due' => round((float) ($item->order->balance_due ?? 0.0), 2),
                'is_rush' => (bool) ($item->order->is_rush ?? false),
            ];
        };

        $currentlyPrinting = $activeItems
            ->where('production_stage', OrderItem::STAGE_PRINTING)
            ->map($mapRow)
            ->values()
            ->all();

        $inFinishing = $activeItems
            ->whereIn('production_stage', [OrderItem::STAGE_BINDING, OrderItem::STAGE_QUALITY_CHECK])
            ->map($mapRow)
            ->values()
            ->all();

        $readyForPickup = $activeItems
            ->where('production_stage', OrderItem::STAGE_READY_FOR_PICKUP)
            ->map($mapRow)
            ->values()
            ->all();

        return [
            'report_type' => 'schedule',
            'title' => 'Live Production Schedule & Job Status Report',
            'period' => 'live',
            'generated_at' => now(),
            'summary' => [
                'workload_status' => $shop->workloadStatus(),
                'active_workload_items' => $shop->activeWorkloadItemCount(),
                'queued_count' => $activeItems->where('production_stage', OrderItem::STAGE_QUEUE)->count(),
                'on_hold_count' => $activeItems->where('production_stage', OrderItem::STAGE_ON_HOLD)->count(),
                'currently_printing_count' => count($currentlyPrinting),
                'in_finishing_count' => count($inFinishing),
                'ready_for_pickup_count' => count($readyForPickup),
                'aging_pickup_count' => collect($readyForPickup)->where('is_aging', true)->count(),
            ],
            'currently_printing' => $currentlyPrinting,
            'in_finishing' => $inFinishing,
            'ready_for_pickup' => $readyForPickup,
        ];
    }

    /**
     * Render any standardized shop report as RFC-4180 CSV text.
     */
    public function exportCsv(PrintShop $shop, string $type, string $period = 'this_month'): string
    {
        $rows = [];

        if ($type === 'inventory') {
            $data = $this->buildInventoryReport($shop, $period);
            $rows[] = ['SKU', 'Material Name', 'Category', 'Type', 'Physical Stock', 'Reserved', 'Available', 'Unit', 'Reorder Level', 'Unit Cost (PHP)', 'Valuation (PHP)', 'Status'];
            foreach ($data['items'] as $item) {
                $rows[] = [
                    $item['sku'],
                    $item['name'],
                    $item['category'],
                    $item['item_type'],
                    $item['stock_qty'],
                    $item['reserved_qty'],
                    $item['available_qty'],
                    $item['unit'],
                    $item['reorder_level'],
                    number_format((float) $item['unit_cost'], 2, '.', ''),
                    number_format((float) $item['stock_value'], 2, '.', ''),
                    $item['status'],
                ];
            }
        } elseif ($type === 'schedule') {
            $data = $this->buildProductionScheduleReport($shop);
            $rows[] = ['Bucket', 'Order #', 'Tracking Code', 'Customer', 'Service', 'Copies', 'Stage', 'Printer', 'Assigned Staff', 'Shelf/Bin', 'Aging Days', 'Aging Flag', 'Balance Due (PHP)'];
            foreach (['Currently Printing' => $data['currently_printing'], 'In Finishing' => $data['in_finishing'], 'Ready for Pickup' => $data['ready_for_pickup']] as $bucket => $bucketRows) {
                foreach ($bucketRows as $row) {
                    $rows[] = [
                        $bucket,
                        $row['order_number'],
                        $row['tracking_code'],
                        $row['customer_name'],
                        $row['service_label'],
                        $row['copies_count'],
                        $row['stage_label'],
                        $row['printer_name'],
                        $row['staff_name'],
                        $row['shelf_bin_code'],
                        $row['aging_days'],
                        $row['is_aging'] ? 'Aging >= 3d' : 'OK',
                        number_format((float) $row['balance_due'], 2, '.', ''),
                    ];
                }
            }
        } else {
            $data = $this->buildSalesReport($shop, $period);
            $rows[] = ['Order #', 'Tracking Code', 'Customer', 'Service', 'Order Status', 'Payment Status', 'Total (PHP)', 'Paid (PHP)', 'Balance Due (PHP)', 'Refund Due (PHP)', 'Date'];
            foreach ($data['orders'] as $row) {
                $rows[] = [
                    $row['order_number'],
                    $row['tracking_code'],
                    $row['customer_name'],
                    $row['service_label'],
                    $row['order_status'],
                    $row['payment_status'],
                    number_format((float) $row['total_amount'], 2, '.', ''),
                    number_format((float) $row['amount_paid'], 2, '.', ''),
                    number_format((float) $row['balance_due'], 2, '.', ''),
                    number_format((float) $row['refund_due'], 2, '.', ''),
                    $row['created_at'],
                ];
            }
        }

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return '';
        }

        foreach ($rows as $csvRow) {
            fputcsv($output, $csvRow);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content !== false ? $content : '';
    }

    /**
     * Resolve start and end timestamps for reporting periods.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7d', 'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'ytd' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}

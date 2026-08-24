<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SalesAnalyticsService
{
    /**
     * Compute financial KPIs and profitability metrics for a print shop.
     *
     * @return array{
     *     gross_revenue: float,
     *     paid_orders_count: int,
     *     all_orders_count: int,
     *     average_order_value: float,
     *     raw_material_cost: float,
     *     spoilage_loss: float,
     *     net_profit: float,
     *     profit_margin_pct: float,
     *     rush_revenue: float,
     *     pending_payments_count: int
     * }
     */
    public function getMetrics(
        PrintShop $shop,
        string $period = 'this_month',
        string $serviceKey = 'all',
        string $fulfillmentType = 'all'
    ): array {
        $orderQuery = $this->buildFilteredOrderQuery($shop, $period, $serviceKey, $fulfillmentType);

        /** @var Collection<int, Order> $orders */
        $orders = $orderQuery->with('items')->get();

        /** @var Collection<int, Order> $validOrders */
        $validOrders = $orders->filter(fn (Order $o): bool => ! in_array($o->payment_status, [Order::PAYMENT_REJECTED, 'cancelled'], true));

        /** @var Collection<int, Order> $paidOrders */
        $paidOrders = $validOrders->filter(fn (Order $o): bool => in_array($o->payment_status, [Order::PAYMENT_VERIFIED_PAID, 'paid', 'verified'], true));

        $grossRevenue = (float) $paidOrders->sum('total_amount');
        $rushRevenue = (float) $paidOrders->sum('rush_fee_amount');
        $paidCount = $paidOrders->count();
        $allCount = $orders->count();
        $pendingPaymentsCount = $orders->where('payment_status', Order::PAYMENT_PENDING_VERIFICATION)->count();
        $aov = $paidCount > 0 ? $grossRevenue / $paidCount : 0.0;

        // Compute Raw Material Cost for valid orders
        $rawMaterialCost = $this->calculateEstimatedMaterialCost($shop, $validOrders);

        // Compute Spoilage Loss in the specified period
        $spoilageLoss = $this->calculateSpoilageLoss($shop, $period);

        // Net Profit = Gross Revenue - Raw Material Cost - Spoilage Loss
        $netProfit = $grossRevenue - $rawMaterialCost - $spoilageLoss;
        $profitMarginPct = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0.0;

        return [
            'gross_revenue' => round($grossRevenue, 2),
            'paid_orders_count' => $paidCount,
            'all_orders_count' => $allCount,
            'average_order_value' => round($aov, 2),
            'raw_material_cost' => round($rawMaterialCost, 2),
            'spoilage_loss' => round($spoilageLoss, 2),
            'net_profit' => round($netProfit, 2),
            'profit_margin_pct' => round($profitMarginPct, 1),
            'rush_revenue' => round($rushRevenue, 2),
            'pending_payments_count' => $pendingPaymentsCount,
        ];
    }

    /**
     * Compute timeline series for revenue and order volume SVG charts.
     *
     * @return array{
     *     labels: array<int, string>,
     *     revenue: array<int, float>,
     *     orders: array<int, int>,
     *     max_revenue: float,
     *     total_revenue: float,
     *     total_orders: int
     * }
     */
    public function getTimelineData(
        PrintShop $shop,
        string $period = 'this_month',
        string $serviceKey = 'all'
    ): array {
        [$startDate, $endDate, $daysCount] = $this->resolveDateRange($period);

        /** @var Collection<int, Order> $orders */
        $orders = Order::where('print_shop_id', $shop->id)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->whereNotIn('payment_status', [Order::PAYMENT_REJECTED, 'cancelled'])
            ->when($serviceKey !== 'all', fn (Builder $q) => $q->where('service_key', $serviceKey))
            ->get();

        $labels = [];
        $revenueSeries = [];
        $orderCountSeries = [];

        // Generate day-by-day intervals
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dayKey = $current->format('Y-m-d');
            $dayLabel = $daysCount <= 7 ? $current->format('D, M j') : $current->format('M j');

            $dayOrders = $orders->filter(fn (Order $o): bool => $o->created_at !== null && $o->created_at->format('Y-m-d') === $dayKey);
            $dayRevenue = (float) $dayOrders->whereIn('payment_status', [Order::PAYMENT_VERIFIED_PAID, 'paid', 'verified'])->sum('total_amount');

            $labels[] = $dayLabel;
            $revenueSeries[] = round($dayRevenue, 2);
            $orderCountSeries[] = $dayOrders->count();

            $current->addDay();
        }

        $maxRevenue = count($revenueSeries) > 0 ? max($revenueSeries) : 0.0;

        return [
            'labels' => $labels,
            'revenue' => $revenueSeries,
            'orders' => $orderCountSeries,
            'max_revenue' => $maxRevenue > 0 ? $maxRevenue : 1000.0,
            'total_revenue' => round(array_sum($revenueSeries), 2),
            'total_orders' => array_sum($orderCountSeries),
        ];
    }

    /**
     * Compute product and fulfillment mix breakdown (Hardbound vs Softbound vs Ring Bound, Service Distribution).
     *
     * @return array{
     *     full_package_count: int,
     *     cover_only_count: int,
     *     hardbound_count: int,
     *     softbound_count: int,
     *     ring_bind_count: int,
     *     rush_count: int,
     *     regular_count: int,
     *     top_colors: array<int, array{color: string, count: int, percentage: float}>,
     *     service_breakdown: array<int, array{service_key: string, name: string, count: int, percentage: float}>
     * }
     */
    public function getProductMix(PrintShop $shop, string $period = 'all'): array
    {
        $orderQuery = $this->buildFilteredOrderQuery($shop, $period, 'all', 'all');
        /** @var Collection<int, Order> $orders */
        $orders = $orderQuery->with('items')->get();

        /** @var Collection<int, OrderItem> $items */
        $items = $orders->flatMap(fn (Order $o) => $o->items);
        $totalItems = max(1, $items->count());
        $totalOrders = max(1, $orders->count());

        $fullPackageCount = $items->filter(fn (OrderItem $i): bool => $i->fulfillment_type === OrderItem::FULFILLMENT_FULL_PACKAGE)->count();
        $coverOnlyCount = $items->filter(fn (OrderItem $i): bool => $i->fulfillment_type === OrderItem::FULFILLMENT_COVER_ONLY)->count();

        $hardboundCount = $items->filter(fn (OrderItem $i): bool => $i->binding_type === 'hardbound')->count();
        $softboundCount = $items->filter(fn (OrderItem $i): bool => $i->binding_type === 'softbound')->count();
        $ringBindCount = $items->filter(fn (OrderItem $i): bool => $i->binding_type === 'ring_bind' || $i->getFinishingType() === 'ring_bind')->count();

        $rushCount = $orders->where('is_rush', true)->count();
        $regularCount = $orders->where('is_rush', false)->count();

        // Top cover leatherette colors
        $colorGroups = $items->filter(fn (OrderItem $i): bool => ! empty($i->cover_color))
            ->groupBy('cover_color')
            ->map(fn (Collection $group, string|int $color): array => [
                'color' => (string) $color,
                'count' => $group->count(),
                'percentage' => round(($group->count() / $totalItems) * 100, 1),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(5)
            ->all();

        // Service Distribution
        $serviceBreakdown = $orders->groupBy('service_key')
            ->map(fn (Collection $group, string|int $key): array => [
                'service_key' => (string) $key,
                'name' => PrintServiceCatalog::find((string) $key)['name'] ?? ucwords(str_replace('_', ' ', (string) $key)),
                'count' => $group->count(),
                'percentage' => round(($group->count() / $totalOrders) * 100, 1),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return [
            'full_package_count' => $fullPackageCount,
            'cover_only_count' => $coverOnlyCount,
            'hardbound_count' => $hardboundCount,
            'softbound_count' => $softboundCount,
            'ring_bind_count' => $ringBindCount,
            'rush_count' => $rushCount,
            'regular_count' => $regularCount,
            'top_colors' => $colorGroups,
            'service_breakdown' => $serviceBreakdown,
        ];
    }

    /**
     * Generate formatted CSV text of all transactions for export.
     */
    public function generateCsvExport(PrintShop $shop, string $period = 'all', string $serviceKey = 'all'): string
    {
        /** @var Collection<int, Order> $orders */
        $orders = $this->buildFilteredOrderQuery($shop, $period, $serviceKey, 'all')
            ->with(['customer', 'items'])
            ->latest('created_at')
            ->get();

        $csv = [];
        $csv[] = ['Order #', 'Date', 'Customer Name', 'Customer Email', 'Service', 'Mode', 'Binding', 'Copies', 'Pages (BW/Col)', 'Total Amount (PHP)', 'Payment Status', 'Order Stage'];

        foreach ($orders as $order) {
            /** @var OrderItem|null $item */
            $item = $order->items->first();
            $csv[] = [
                $order->order_number,
                $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
                $order->customer ? $order->customer->name : 'Guest',
                $order->customer ? $order->customer->email : '',
                $order->service_key,
                $item ? $item->fulfillment_type : 'full_package',
                $item ? $item->binding_type : 'hardbound',
                $item ? $item->copies_count : 1,
                ($item ? (string) $item->bw_pages_count : '0').' / '.($item ? (string) $item->color_pages_count : '0'),
                number_format($order->total_amount, 2, '.', ''),
                $order->payment_status,
                $order->stageLabel(),
            ];
        }

        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            return '';
        }

        foreach ($csv as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content !== false ? $content : '';
    }

    /**
     * Build base filtered order query.
     *
     * @return Builder<Order>
     */
    protected function buildFilteredOrderQuery(
        PrintShop $shop,
        string $period,
        string $serviceKey,
        string $fulfillmentType
    ): Builder {
        /** @var Builder<Order> $query */
        $query = Order::where('print_shop_id', $shop->id);

        if ($serviceKey !== 'all') {
            $query->where('service_key', $serviceKey);
        }

        if ($fulfillmentType !== 'all') {
            $query->whereHas('items', fn (Builder $q) => $q->where('fulfillment_type', $fulfillmentType));
        }

        if ($period !== 'all_time' && $period !== 'all') {
            [$startDate, $endDate] = $this->resolveDateRange($period);
            $query->where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Calculate consumed raw materials cost based on inventory unit costs.
     *
     * @param  Collection<int, Order>  $orders
     */
    protected function calculateEstimatedMaterialCost(PrintShop $shop, Collection $orders): float
    {
        $items = $shop->inventoryItems;
        /** @var InventoryItem|null $paperItem */
        $paperItem = $items->first(fn (InventoryItem $i): bool => str_contains(strtolower($i->name), 'bond') || str_contains(strtolower($i->name), 'paper') || $i->category === 'Paper Stock');
        /** @var InventoryItem|null $boardItem */
        $boardItem = $items->first(fn (InventoryItem $i): bool => str_contains(strtolower($i->name), 'chipboard') || str_contains(strtolower($i->name), 'board'));
        /** @var InventoryItem|null $leatherItem */
        $leatherItem = $items->first(fn (InventoryItem $i): bool => str_contains(strtolower($i->name), 'leather') || str_contains(strtolower($i->name), 'cover'));
        /** @var InventoryItem|null $foilItem */
        $foilItem = $items->first(fn (InventoryItem $i): bool => str_contains(strtolower($i->name), 'foil') || str_contains(strtolower($i->name), 'gold'));

        $paperUnitCost = $paperItem ? (float) $paperItem->unit_cost : 0.50;
        $boardUnitCost = $boardItem ? (float) $boardItem->unit_cost : 25.00;
        $leatherUnitCost = $leatherItem ? (float) $leatherItem->unit_cost : 35.00;
        $foilUnitCost = $foilItem ? (float) $foilItem->unit_cost : 5.00;

        $totalCost = 0.0;

        foreach ($orders as $order) {
            foreach ($order->items as $orderItem) {
                $copies = max(1, $orderItem->copies_count);
                $isCoverOnly = $orderItem->isCoverOnly();

                if ($order->service_key === 'document_printing' || $orderItem->isDocumentPrinting()) {
                    $sheets = $orderItem->getPhysicalSheetsCount();
                    $totalCost += ($sheets * $paperUnitCost);

                    if ($orderItem->getFinishingType() === 'ring_bind') {
                        $totalCost += (15.00 * $copies); // Ring spine + 2x acetate + back board
                    } elseif ($orderItem->getFinishingType() === 'folder') {
                        $totalCost += (8.00 * $copies);
                    }
                } else {
                    // Paper cost (0 if Cover-Only)
                    if (! $isCoverOnly) {
                        $pages = (int) $orderItem->total_pages_count;
                        $totalCost += ($pages * $paperUnitCost * $copies);
                    }

                    // Hardbound board & leatherette
                    if ($orderItem->binding_type === 'hardbound') {
                        $totalCost += ($boardUnitCost * $copies);
                        $totalCost += ($leatherUnitCost * $copies);
                        $totalCost += ($foilUnitCost * $copies);
                    } else {
                        // Softbound thermal cover
                        $totalCost += (15.00 * $copies);
                    }
                }
            }
        }

        return $totalCost;
    }

    /**
     * Calculate financial monetary loss from recorded material spoilage.
     */
    protected function calculateSpoilageLoss(PrintShop $shop, string $period): float
    {
        $itemIds = $shop->inventoryItems()->pluck('id');
        if ($itemIds->isEmpty()) {
            return 0.0;
        }

        $query = StockMovement::whereIn('inventory_item_id', $itemIds)
            ->where('movement_type', StockMovement::TYPE_SPOILAGE_WASTE)
            ->with('inventoryItem');

        if ($period !== 'all_time' && $period !== 'all') {
            [$startDate, $endDate] = $this->resolveDateRange($period);
            $query->where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate);
        }

        /** @var Collection<int, StockMovement> $movements */
        $movements = $query->get();

        $totalLoss = 0.0;
        foreach ($movements as $movement) {
            $unitCost = $movement->inventoryItem ? (float) $movement->inventoryItem->unit_cost : 0.0;
            $qtyWasted = abs((float) $movement->quantity);
            $totalLoss += ($qtyWasted * $unitCost);
        }

        return $totalLoss;
    }

    /**
     * Resolve date range for a given period.
     *
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    protected function resolveDateRange(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                1,
            ],
            'last_7_days', '7d' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
                7,
            ],
            'this_month', '30d', 'last_30_days' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                30,
            ],
            'ytd' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfDay(),
                $now->dayOfYear,
            ],
            default => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
                30,
            ],
        };
    }
}

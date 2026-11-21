<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\DocumentPrintingConfig;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\ServiceBom;
use App\Models\StockMovement;
use App\Models\ThesisBindingBomItem;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryDeductionService
{
    /**
     * Reserve raw materials atomically across order line items using deterministic row locking.
     *
     * @return array<int, array{inventory_item_id: int, item_name: string, quantity_reserved: float, unit: string, remaining_available: float}>
     *
     * @throws InsufficientStockException
     */
    public function reserveForOrderItems(Order $order, ?User $actor = null): array
    {
        $order->loadMissing(['items', 'printShop']);
        $shop = $order->printShop;
        if (! $shop) {
            return [];
        }

        /** @var array<int, array<int, array{item_id: int, quantity: float, note: string}>> $perOrderItem */
        $perOrderItem = [];
        /** @var array<int, float> $totalsByItemId */
        $totalsByItemId = [];

        foreach ($order->items as $orderItem) {
            $requirements = $this->calculateRequiredMaterialsForItem($shop, $order, $orderItem);
            if ($requirements === []) {
                continue;
            }

            $perOrderItem[$orderItem->id] = $requirements;
            foreach ($requirements as $req) {
                $itemId = $req['item_id'];
                $totalsByItemId[$itemId] = round(($totalsByItemId[$itemId] ?? 0.0) + $req['quantity'], 2);
            }
        }

        if ($totalsByItemId === []) {
            return [];
        }

        $reservations = [];

        DB::transaction(function () use ($order, $actor, $perOrderItem, $totalsByItemId, &$reservations): void {
            $itemIds = array_keys($totalsByItemId);
            sort($itemIds, SORT_NUMERIC);

            /** @var Collection<int, InventoryItem> $lockedItems */
            $lockedItems = InventoryItem::query()
                ->where('print_shop_id', $order->print_shop_id)
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($itemIds as $itemId) {
                $lockedItem = $lockedItems->get($itemId);
                $needed = $totalsByItemId[$itemId];

                if (! $lockedItem instanceof InventoryItem) {
                    throw new InsufficientStockException(
                        inventoryItemId: $itemId,
                        itemName: "Inventory Item #{$itemId}",
                        requestedQty: $needed,
                        availableQty: 0.0,
                        unit: 'units',
                    );
                }

                $available = $lockedItem->availableQty();
                if ($available + 0.0001 < $needed) {
                    throw new InsufficientStockException(
                        inventoryItemId: $lockedItem->id,
                        itemName: $lockedItem->name,
                        requestedQty: $needed,
                        availableQty: $available,
                        unit: $lockedItem->unit,
                        fallbackSuggestions: $this->findFallbackSuggestions($order->print_shop_id, $lockedItem, $needed),
                    );
                }
            }

            foreach ($perOrderItem as $orderItemId => $requirements) {
                foreach ($requirements as $req) {
                    /** @var InventoryItem $lockedItem */
                    $lockedItem = $lockedItems->get($req['item_id']);
                    $qty = round($req['quantity'], 2);
                    if ($qty <= 0.0) {
                        continue;
                    }

                    $lockedItem->reserved_qty = round((float) $lockedItem->reserved_qty + $qty, 2);
                    $lockedItem->save();

                    StockMovement::create([
                        'inventory_item_id' => $lockedItem->id,
                        'order_item_id' => $orderItemId,
                        'movement_type' => StockMovement::TYPE_RESERVATION,
                        'quantity' => $qty,
                        'previous_stock' => (float) $lockedItem->stock_qty,
                        'resulting_stock' => (float) $lockedItem->stock_qty,
                        'reference_note' => "Reserved for Order {$order->order_number}: {$req['note']}",
                        'logged_by' => $actor?->id,
                    ]);

                    $reservations[] = [
                        'inventory_item_id' => $lockedItem->id,
                        'item_name' => $lockedItem->name,
                        'quantity_reserved' => $qty,
                        'unit' => $lockedItem->unit,
                        'remaining_available' => $lockedItem->availableQty(),
                    ];
                }
            }
        });

        return $reservations;
    }

    /**
     * Release any unreleased stock reservations for an order idempotently.
     *
     * @return array<int, array{inventory_item_id: int, item_name: string, quantity_released: float, unit: string}>
     */
    public function releaseReservation(Order $order, ?User $actor = null, string $reason = 'Order cancelled'): array
    {
        $orderItemIds = $order->items()->pluck('id');
        if ($orderItemIds->isEmpty()) {
            return [];
        }

        $released = [];

        DB::transaction(function () use ($order, $orderItemIds, $actor, $reason, &$released): void {
            $unreleasedByPair = $this->calculateUnreleasedReservations($orderItemIds);
            if ($unreleasedByPair === []) {
                return;
            }

            $itemIds = array_values(array_unique(array_column($unreleasedByPair, 'inventory_item_id')));
            sort($itemIds, SORT_NUMERIC);

            /** @var Collection<int, InventoryItem> $lockedItems */
            $lockedItems = InventoryItem::query()
                ->where('print_shop_id', $order->print_shop_id)
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($unreleasedByPair as $entry) {
                $lockedItem = $lockedItems->get($entry['inventory_item_id']);
                if (! $lockedItem instanceof InventoryItem) {
                    continue;
                }

                $releaseQty = min((float) $lockedItem->reserved_qty, $entry['quantity']);
                if ($releaseQty <= 0.0) {
                    continue;
                }

                $lockedItem->reserved_qty = max(0.0, round((float) $lockedItem->reserved_qty - $releaseQty, 2));
                $lockedItem->save();

                StockMovement::create([
                    'inventory_item_id' => $lockedItem->id,
                    'order_item_id' => $entry['order_item_id'],
                    'movement_type' => StockMovement::TYPE_RESERVATION_RELEASE,
                    'quantity' => -$releaseQty,
                    'previous_stock' => (float) $lockedItem->stock_qty,
                    'resulting_stock' => (float) $lockedItem->stock_qty,
                    'reference_note' => "Reservation released for Order {$order->order_number}: {$reason}",
                    'logged_by' => $actor?->id,
                ]);

                $released[] = [
                    'inventory_item_id' => $lockedItem->id,
                    'item_name' => $lockedItem->name,
                    'quantity_released' => $releaseQty,
                    'unit' => $lockedItem->unit,
                ];
            }
        });

        return $released;
    }

    /**
     * Convert reserved stock into actual physical stock deduction upon print start / completion.
     *
     * @return array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>
     */
    public function commitReservationOnPrint(Order $order, ?User $staff = null, ?OrderItem $singleItem = null): array
    {
        $order->loadMissing(['items', 'printShop']);
        $shop = $order->printShop;
        if (! $shop) {
            return [];
        }

        $targetItems = $singleItem !== null ? collect([$singleItem]) : $order->items;
        if ($targetItems->isEmpty()) {
            return [];
        }

        /** @var array<int, array<int, array{item_id: int, quantity: float, note: string}>> $perOrderItem */
        $perOrderItem = [];
        /** @var array<int, int> $involvedItemIds */
        $involvedItemIds = [];

        foreach ($targetItems as $orderItem) {
            $requirements = $this->calculateRequiredMaterialsForItem($shop, $order, $orderItem);
            if ($requirements === []) {
                continue;
            }

            $perOrderItem[$orderItem->id] = $requirements;
            foreach ($requirements as $req) {
                $involvedItemIds[$req['item_id']] = $req['item_id'];
            }
        }

        if ($perOrderItem === []) {
            return [];
        }

        $deductions = [];

        DB::transaction(function () use ($order, $targetItems, $perOrderItem, $involvedItemIds, $staff, &$deductions): void {
            $itemIds = array_values($involvedItemIds);
            sort($itemIds, SORT_NUMERIC);

            /** @var Collection<int, InventoryItem> $lockedItems */
            $lockedItems = InventoryItem::query()
                ->where('print_shop_id', $order->print_shop_id)
                ->whereIn('id', $itemIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $unreleasedByPair = $this->calculateUnreleasedReservations($targetItems->pluck('id'));

            foreach ($perOrderItem as $orderItemId => $requirements) {
                foreach ($requirements as $req) {
                    $lockedItem = $lockedItems->get($req['item_id']);
                    if (! $lockedItem instanceof InventoryItem) {
                        continue;
                    }

                    $consumedQty = round($req['quantity'], 2);
                    if ($consumedQty <= 0.0) {
                        continue;
                    }

                    $pairKey = $orderItemId.':'.$lockedItem->id;
                    $reservedForPair = $unreleasedByPair[$pairKey]['quantity'] ?? 0.0;
                    $releaseFromReserve = min((float) $lockedItem->reserved_qty, min($reservedForPair, $consumedQty));

                    if ($releaseFromReserve > 0.0 && isset($unreleasedByPair[$pairKey])) {
                        $unreleasedByPair[$pairKey]['quantity'] = max(0.0, round($reservedForPair - $releaseFromReserve, 2));
                    }

                    $prevStock = (float) $lockedItem->stock_qty;
                    $newStock = max(0.0, round($prevStock - $consumedQty, 2));

                    $lockedItem->stock_qty = $newStock;
                    $lockedItem->reserved_qty = max(0.0, round((float) $lockedItem->reserved_qty - $releaseFromReserve, 2));
                    $lockedItem->save();

                    StockMovement::create([
                        'inventory_item_id' => $lockedItem->id,
                        'order_item_id' => $orderItemId,
                        'movement_type' => StockMovement::TYPE_PRODUCTION_DEDUCTION,
                        'quantity' => -$consumedQty,
                        'previous_stock' => $prevStock,
                        'resulting_stock' => $newStock,
                        'reference_note' => "Order {$order->order_number} ({$req['note']})",
                        'logged_by' => $staff?->id,
                    ]);

                    $deductions[] = [
                        'item_name' => $lockedItem->name,
                        'quantity_deducted' => $consumedQty,
                        'unit' => $lockedItem->unit,
                        'remaining_stock' => $newStock,
                    ];
                }
            }
        });

        return $deductions;
    }

    /**
     * Deducts raw materials for an order based on BOM recipe configuration.
     *
     * @return array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>
     */
    public function deductForOrder(Order $order, ?User $staff = null): array
    {
        return $this->commitReservationOnPrint($order, $staff);
    }

    /**
     * Calculate raw material requirements for a single order item without mutating inventory.
     *
     * @return array<int, array{item_id: int, quantity: float, note: string}>
     */
    public function calculateRequiredMaterialsForItem(PrintShop $shop, Order $order, OrderItem $orderItem): array
    {
        $serviceKey = $orderItem->resolvedServiceKey();
        $requirements = [];

        /** @var Collection<int, ServiceBom> $serviceBoms */
        $serviceBoms = ServiceBom::where('print_shop_id', $shop->id)
            ->where('service_key', $serviceKey)
            ->with('inventoryItem')
            ->get();

        if ($serviceBoms->isNotEmpty()) {
            $requirements = $this->resolveRequirementsFromServiceBoms($orderItem, $serviceBoms);
        } elseif ($serviceKey === 'document_printing') {
            $requirements = $this->resolveRequirementsForDocumentPrinting($shop, $orderItem);
        } else {
            $requirements = $this->resolveRequirementsForThesisBinding($shop, $orderItem);
        }

        return $this->mergeRequirementsByItem($requirements);
    }

    /**
     * Record material wastage or spoilage.
     */
    public function recordSpoilage(
        InventoryItem $item,
        float $wastedQty,
        string $reason,
        ?User $staff = null,
        ?int $orderItemId = null,
        string $movementType = StockMovement::TYPE_SPOILAGE_WASTE,
    ): StockMovement {
        return DB::transaction(function () use ($item, $wastedQty, $reason, $staff, $orderItemId, $movementType): StockMovement {
            $lockedItem = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $prevStock = (float) $lockedItem->stock_qty;
            $newStock = max(0.0, round($prevStock - $wastedQty, 2));

            $lockedItem->update(['stock_qty' => $newStock]);

            return StockMovement::create([
                'inventory_item_id' => $lockedItem->id,
                'order_item_id' => $orderItemId,
                'movement_type' => $movementType,
                'quantity' => -$wastedQty,
                'previous_stock' => $prevStock,
                'resulting_stock' => $newStock,
                'reference_note' => "Spoilage/Waste: {$reason}",
                'logged_by' => $staff?->id,
            ]);
        });
    }

    /**
     * Check if an inventory item represents paper stock.
     */
    protected function isPaperItem(InventoryItem $item): bool
    {
        $name = strtolower($item->name);
        $category = strtolower($item->category);

        return str_contains($name, 'paper') ||
            str_contains($name, 'bond') ||
            str_contains($name, 'gsm') ||
            str_contains($category, 'paper') ||
            str_contains($category, 'stock');
    }

    /**
     * Resolve BOM requirements from unified ServiceBom definitions.
     *
     * @param  Collection<int, ServiceBom>  $serviceBoms
     * @return array<int, array{item_id: int, quantity: float, note: string}>
     */
    protected function resolveRequirementsFromServiceBoms(OrderItem $orderItem, Collection $serviceBoms): array
    {
        $isCoverOnly = $orderItem->isCoverOnly();
        $copies = max(1, $orderItem->copies_count);
        $totalPages = max(1, (int) $orderItem->total_pages_count);
        $physicalSheets = $orderItem->getPhysicalSheetsCount();
        $requirements = [];

        foreach ($serviceBoms as $bom) {
            $item = $bom->inventoryItem;
            if (! $item instanceof InventoryItem) {
                continue;
            }

            if (! empty($bom->conditions)) {
                $matched = true;
                foreach ($bom->conditions as $key => $val) {
                    $actual = $orderItem->{$key}
                        ?? data_get($orderItem->specifications, $key)
                        ?? data_get($orderItem->custom_fields_data, $key);

                    if ($actual === null || ! str_contains(strtolower((string) $actual), strtolower((string) $val))) {
                        $matched = false;
                        break;
                    }
                }

                if (! $matched) {
                    continue;
                }
            }

            if ($isCoverOnly && $this->isPaperItem($item)) {
                continue;
            }

            $consumedQty = match ($bom->usage_type) {
                ServiceBom::USAGE_PER_PAGE => (float) ($totalPages * $copies * $bom->usage_qty),
                ServiceBom::USAGE_PER_SHEET => (float) ($physicalSheets * $bom->usage_qty),
                ServiceBom::USAGE_FIXED => (float) $bom->usage_qty,
                default => (float) ($copies * $bom->usage_qty),
            };

            if ($consumedQty > 0.0) {
                $requirements[] = [
                    'item_id' => $item->id,
                    'quantity' => $consumedQty,
                    'note' => $bom->component_name,
                ];
            }
        }

        return $requirements;
    }

    /**
     * Resolve BOM requirements for a Document Printing order item.
     *
     * @return array<int, array{item_id: int, quantity: float, note: string}>
     */
    protected function resolveRequirementsForDocumentPrinting(PrintShop $shop, OrderItem $orderItem): array
    {
        $config = DocumentPrintingConfig::where('print_shop_id', $shop->id)->first();
        if ($config && ! $config->auto_deduct_inventory) {
            return [];
        }

        $requirements = [];
        $copies = max(1, $orderItem->copies_count);
        $physicalSheets = $orderItem->getPhysicalSheetsCount();
        $paperSize = strtolower($orderItem->paper_size);

        $paperItem = null;
        if ($config) {
            if (str_contains($paperSize, 'a4') && $config->bom_a4_paper_item_id) {
                $paperItem = $config->a4PaperItem;
            } elseif ((str_contains($paperSize, 'long') || str_contains($paperSize, 'legal')) && $config->bom_long_paper_item_id) {
                $paperItem = $config->longPaperItem;
            } elseif ($config->bom_short_paper_item_id) {
                $paperItem = $config->shortPaperItem;
            }
        }

        if (! $paperItem instanceof InventoryItem) {
            $paperItem = $shop->inventoryItems()
                ->where(function (Builder $q) use ($paperSize): void {
                    if (str_contains($paperSize, 'a4')) {
                        $q->where('name', 'like', '%A4%');
                    } elseif (str_contains($paperSize, 'long') || str_contains($paperSize, 'legal')) {
                        $q->where('name', 'like', '%Long%');
                    } else {
                        $q->where('name', 'like', '%Short%')->orWhere('name', 'like', '%Letter%');
                    }
                })
                ->first();
        }

        if ($paperItem instanceof InventoryItem && $physicalSheets > 0) {
            $requirements[] = [
                'item_id' => $paperItem->id,
                'quantity' => (float) $physicalSheets,
                'note' => "Doc Print {$orderItem->paper_size} {$orderItem->getPrintSides()}",
            ];
        }

        $finishing = $orderItem->getFinishingType();
        if ($finishing === 'ring_bind') {
            $ringItem = ($config && $config->bom_ring_spine_item_id) ? $config->ringSpineItem : null;
            if (! $ringItem instanceof InventoryItem) {
                $ringItem = $shop->inventoryItems()
                    ->where(fn (Builder $q) => $q->where('name', 'like', '%Ring%')->orWhere('name', 'like', '%Comb%'))
                    ->first();
            }
            if ($ringItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $ringItem->id,
                    'quantity' => (float) $copies,
                    'note' => 'Plastic Ring Comb',
                ];
            }

            $acetateItem = ($config && $config->bom_pvc_acetate_item_id) ? $config->pvcAcetateItem : null;
            if (! $acetateItem instanceof InventoryItem) {
                $acetateItem = $shop->inventoryItems()
                    ->where(fn (Builder $q) => $q->where('name', 'like', '%Acetate%')->orWhere('name', 'like', '%PVC%'))
                    ->first();
            }
            if ($acetateItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $acetateItem->id,
                    'quantity' => (float) ($copies * 2),
                    'note' => 'Clear PVC Acetate Covers',
                ];
            }

            $boardItem = ($config && $config->bom_back_cover_item_id) ? $config->backCoverItem : null;
            if (! $boardItem instanceof InventoryItem) {
                $boardItem = $shop->inventoryItems()
                    ->where(fn (Builder $q) => $q->where('name', 'like', '%Morocco%')->orWhere('name', 'like', '%Board%'))
                    ->first();
            }
            if ($boardItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $boardItem->id,
                    'quantity' => (float) $copies,
                    'note' => 'Morocco Back Board',
                ];
            }
        } elseif ($finishing === 'folder') {
            $folderItem = $shop->inventoryItems()->where('name', 'like', '%Folder%')->first();
            if ($folderItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $folderItem->id,
                    'quantity' => (float) $copies,
                    'note' => 'Sliding Folder',
                ];
            }
        }

        return $requirements;
    }

    /**
     * Resolve BOM requirements for a Thesis Binding order item.
     *
     * @return array<int, array{item_id: int, quantity: float, note: string}>
     */
    protected function resolveRequirementsForThesisBinding(PrintShop $shop, OrderItem $orderItem): array
    {
        $config = ThesisBindingConfig::where('print_shop_id', $shop->id)->first();
        if ($config && ! $config->auto_deduct_inventory) {
            return [];
        }

        $isCoverOnly = $orderItem->isCoverOnly();
        $copies = max(1, $orderItem->copies_count);
        $totalPages = max(0, (int) $orderItem->total_pages_count);
        $sheetsPerCopy = $totalPages > 0 ? $orderItem->getSheetsPerCopyForBinding() : 0;
        $requirements = [];

        /** @var Collection<int, ThesisBindingBomItem> $bomItems */
        $bomItems = $config
            ? ThesisBindingBomItem::where('thesis_binding_config_id', $config->id)
                ->where(function (Builder $q) use ($orderItem): void {
                    $q->where('binding_type', $orderItem->binding_type)
                        ->orWhere('binding_type', 'both');
                })
                ->with('inventoryItem')
                ->get()
            : collect();

        if ($bomItems->isNotEmpty()) {
            foreach ($bomItems as $bomItem) {
                $inventoryItem = $bomItem->inventoryItem;
                if (! $inventoryItem instanceof InventoryItem) {
                    continue;
                }

                $isPaper = $this->isPaperItem($inventoryItem);
                if ($isCoverOnly && $isPaper) {
                    continue;
                }

                $consumedQty = $isPaper
                    ? (float) ($sheetsPerCopy * $copies)
                    : (float) ($bomItem->usage_qty * $copies);

                if ($consumedQty <= 0.0) {
                    continue;
                }

                $requirements[] = [
                    'item_id' => $inventoryItem->id,
                    'quantity' => $consumedQty,
                    'note' => $orderItem->binding_type.' '.($isCoverOnly ? 'Cover Only' : 'Full Package'),
                ];
            }

            return $requirements;
        }

        if (! $isCoverOnly && $sheetsPerCopy > 0) {
            $paperItem = $shop->inventoryItems()
                ->where(fn (Builder $q) => $q->where('name', 'like', '%Bond Paper%')->orWhere('name', 'like', '%Paper%'))
                ->first();

            if ($paperItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $paperItem->id,
                    'quantity' => (float) ($sheetsPerCopy * $copies),
                    'note' => 'Manuscript Pages',
                ];
            }
        }

        if ($orderItem->binding_type === 'hardbound') {
            $boardItem = $shop->inventoryItems()
                ->where(fn (Builder $q) => $q->where('name', 'like', '%Chipboard%')->orWhere('name', 'like', '%Board%'))
                ->first();
            if ($boardItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $boardItem->id,
                    'quantity' => (float) $copies,
                    'note' => 'Hardbound Chipboard',
                ];
            }

            $leatherItem = $shop->inventoryItems()
                ->where(fn (Builder $q) => $q->where('name', 'like', '%Leatherette%')->orWhere('name', 'like', '%Leather%'))
                ->first();
            if ($leatherItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $leatherItem->id,
                    'quantity' => (float) $copies,
                    'note' => 'Leatherette Cover',
                ];
            }

            $foilItem = $shop->inventoryItems()->where('name', 'like', '%Foil%')->first();
            if ($foilItem instanceof InventoryItem) {
                $requirements[] = [
                    'item_id' => $foilItem->id,
                    'quantity' => (float) $copies,
                    'note' => 'Foil Stamping',
                ];
            }
        }

        return $requirements;
    }

    /**
     * Merge duplicate inventory item lines within the same order item.
     *
     * @param  array<int, array{item_id: int, quantity: float, note: string}>  $requirements
     * @return array<int, array{item_id: int, quantity: float, note: string}>
     */
    protected function mergeRequirementsByItem(array $requirements): array
    {
        /** @var array<int, array{item_id: int, quantity: float, note: string}> $merged */
        $merged = [];
        foreach ($requirements as $req) {
            $itemId = $req['item_id'];
            if (isset($merged[$itemId])) {
                $merged[$itemId]['quantity'] = round($merged[$itemId]['quantity'] + $req['quantity'], 2);
                if (! str_contains($merged[$itemId]['note'], $req['note'])) {
                    $merged[$itemId]['note'] .= ' + '.$req['note'];
                }
            } else {
                $merged[$itemId] = $req;
            }
        }

        return array_values($merged);
    }

    /**
     * Compute unreleased reservation quantities grouped by order item and inventory item.
     *
     * @param  Collection<int, int>  $orderItemIds
     * @return array<string, array{order_item_id: int, inventory_item_id: int, quantity: float}>
     */
    protected function calculateUnreleasedReservations(Collection $orderItemIds): array
    {
        $movements = StockMovement::query()
            ->whereIn('order_item_id', $orderItemIds)
            ->whereIn('movement_type', [
                StockMovement::TYPE_RESERVATION,
                StockMovement::TYPE_RESERVATION_RELEASE,
                StockMovement::TYPE_PRODUCTION_DEDUCTION,
            ])
            ->get();

        /** @var array<string, array{order_item_id: int, inventory_item_id: int, quantity: float}> $balances */
        $balances = [];

        foreach ($movements as $movement) {
            $orderItemId = $movement->order_item_id;
            if ($orderItemId === null) {
                continue;
            }

            $key = $orderItemId.':'.$movement->inventory_item_id;
            if (! isset($balances[$key])) {
                $balances[$key] = [
                    'order_item_id' => $orderItemId,
                    'inventory_item_id' => $movement->inventory_item_id,
                    'quantity' => 0.0,
                ];
            }

            $qty = abs((float) $movement->quantity);
            if ($movement->movement_type === StockMovement::TYPE_RESERVATION) {
                $balances[$key]['quantity'] = round($balances[$key]['quantity'] + $qty, 2);
            } else {
                $balances[$key]['quantity'] = max(0.0, round($balances[$key]['quantity'] - $qty, 2));
            }
        }

        return array_filter($balances, fn (array $entry): bool => $entry['quantity'] > 0.0);
    }

    /**
     * Suggest alternative in-stock inventory items from the same shop and category/paper family.
     *
     * @return array<int, array{id: int, name: string, available_qty: float, unit: string, category: string}>
     */
    protected function findFallbackSuggestions(int $printShopId, InventoryItem $shortItem, float $requiredQty): array
    {
        $isPaper = $this->isPaperItem($shortItem);

        return InventoryItem::query()
            ->where('print_shop_id', $printShopId)
            ->where('id', '!=', $shortItem->id)
            ->where(function (Builder $query) use ($shortItem, $isPaper): void {
                $query->where('category', $shortItem->category);
                if ($isPaper) {
                    $query->orWhere('name', 'like', '%Paper%')
                        ->orWhere('name', 'like', '%Bond%');
                }
            })
            ->get()
            ->filter(fn (InventoryItem $candidate): bool => $candidate->availableQty() + 0.0001 >= $requiredQty)
            ->sortByDesc(fn (InventoryItem $candidate): float => $candidate->availableQty())
            ->take(5)
            ->map(fn (InventoryItem $candidate): array => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'available_qty' => $candidate->availableQty(),
                'unit' => $candidate->unit,
                'category' => $candidate->category,
            ])
            ->values()
            ->all();
    }
}

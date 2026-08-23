<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\ThesisBindingBomItem;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryDeductionService
{
    /**
     * Deducts raw materials for an order based on BOM recipe configuration.
     *
     * @return array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>
     */
    public function deductForOrder(Order $order, ?User $staff = null): array
    {
        $deductions = [];

        $shop = $order->printShop;
        if (! $shop) {
            return $deductions;
        }

        $config = ThesisBindingConfig::where('print_shop_id', $shop->id)->first();
        if ($config && ! $config->auto_deduct_inventory) {
            return $deductions;
        }

        DB::transaction(function () use ($order, $config, $staff, &$deductions) {
            foreach ($order->items as $orderItem) {
                $isCoverOnly = $orderItem->isCoverOnly();
                $copies = max(1, $orderItem->copies_count);
                $totalPages = $orderItem->total_pages_count ?? 0;

                // 1. Fetch explicit BOM recipe items if configured
                $bomItems = $config
                    ? ThesisBindingBomItem::where('thesis_binding_config_id', $config->id)
                        ->where(function ($q) use ($orderItem) {
                            $q->where('binding_type', $orderItem->binding_type)
                                ->orWhere('binding_type', 'both');
                        })
                        ->with('inventoryItem')
                        ->get()
                    : collect();

                if ($bomItems->isNotEmpty()) {
                    foreach ($bomItems as $bomItem) {
                        $inventoryItem = $bomItem->inventoryItem;
                        if (! $inventoryItem) {
                            continue;
                        }

                        $isPaper = $this->isPaperItem($inventoryItem);

                        // If Cover-Only ("Dala ang Papel"), skip paper sheet consumption
                        if ($isCoverOnly && $isPaper) {
                            continue;
                        }

                        // Determine usage quantity
                        if ($isPaper) {
                            $consumedQty = $totalPages * $copies;
                        } else {
                            $consumedQty = $bomItem->usage_qty * $copies;
                        }

                        if ($consumedQty <= 0) {
                            continue;
                        }

                        $prevStock = (float) $inventoryItem->stock_qty;
                        $newStock = max(0, $prevStock - $consumedQty);

                        $inventoryItem->update(['stock_qty' => $newStock]);

                        StockMovement::create([
                            'inventory_item_id' => $inventoryItem->id,
                            'movement_type' => StockMovement::TYPE_PRODUCTION_DEDUCTION,
                            'quantity' => -$consumedQty,
                            'previous_stock' => $prevStock,
                            'resulting_stock' => $newStock,
                            'reference_note' => "Order {$order->order_number} ({$orderItem->binding_type} ".($isCoverOnly ? 'Cover Only' : 'Full Package').')',
                            'logged_by' => $staff?->id,
                        ]);

                        $deductions[] = [
                            'item_name' => $inventoryItem->name,
                            'quantity_deducted' => $consumedQty,
                            'unit' => $inventoryItem->unit,
                            'remaining_stock' => $newStock,
                        ];
                    }
                } else {
                    // Fallback: Smart deduction from shop inventory matching keywords
                    $this->fallbackDeduction($order, $orderItem, $staff, $deductions);
                }
            }
        });

        return $deductions;
    }

    /**
     * Fallback smart deduction when specific BOM recipes have not been explicitly linked.
     *
     * @param  array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>  $deductions
     */
    protected function fallbackDeduction(Order $order, mixed $orderItem, ?User $staff, array &$deductions): void
    {
        $shopId = $order->print_shop_id;
        $copies = max(1, $orderItem->copies_count);
        $isCoverOnly = $orderItem->isCoverOnly();

        // 1. Paper deduction (Only for full package)
        if (! $isCoverOnly && ($orderItem->total_pages_count > 0)) {
            $paper = InventoryItem::where('print_shop_id', $shopId)
                ->where(function ($q) {
                    $q->where('category', 'Paper')
                        ->orWhere('name', 'like', '%Paper%')
                        ->orWhere('name', 'like', '%80gsm%')
                        ->orWhere('name', 'like', '%70gsm%');
                })->first();

            if ($paper) {
                $qty = $orderItem->total_pages_count * $copies;
                $this->applyDeduction($paper, $qty, "Order {$order->order_number} Page Printing", $order, $staff, $deductions);
            }
        }

        // 2. Chipboard deduction (For hardbound)
        if ($orderItem->binding_type === 'hardbound') {
            $chipboard = InventoryItem::where('print_shop_id', $shopId)
                ->where(function ($q) {
                    $q->where('name', 'like', '%Chipboard%')
                        ->orWhere('name', 'like', '%Board%')
                        ->orWhere('category', 'Raw Material');
                })->first();

            if ($chipboard) {
                $this->applyDeduction($chipboard, 1.0 * $copies, "Order {$order->order_number} Hardbound Case", $order, $staff, $deductions);
            }
        }

        // 3. Leatherette cover deduction
        if ($orderItem->binding_type === 'hardbound') {
            $leatherette = InventoryItem::where('print_shop_id', $shopId)
                ->where(function ($q) use ($orderItem) {
                    $q->where('name', 'like', '%Leatherette%')
                        ->orWhere('name', 'like', '%'.($orderItem->cover_color ?? '').'%');
                })->first();

            if ($leatherette) {
                $this->applyDeduction($leatherette, 1.0 * $copies, "Order {$order->order_number} Cover Wrapping", $order, $staff, $deductions);
            }
        }
    }

    /**
     * Helper to apply single inventory deduction and record stock movement.
     *
     * @param  array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>  $deductions
     *
     * @param-out  array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>  $deductions
     */
    protected function applyDeduction(InventoryItem $item, float $qty, string $note, Order $order, ?User $staff, array &$deductions): void
    {
        $prev = (float) $item->stock_qty;
        $new = (float) max(0, $prev - $qty);

        $item->update(['stock_qty' => $new]);

        StockMovement::create([
            'inventory_item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_PRODUCTION_DEDUCTION,
            'quantity' => -$qty,
            'previous_stock' => $prev,
            'resulting_stock' => $new,
            'reference_note' => $note,
            'logged_by' => $staff?->id,
        ]);

        $deductions[] = [
            'item_name' => $item->name,
            'quantity_deducted' => $qty,
            'unit' => $item->unit,
            'remaining_stock' => $new,
        ];
    }

    /**
     * Records material wastage/spoilage reported by production staff.
     */
    public function recordSpoilage(InventoryItem $item, float $qty, string $reason, ?User $staff = null): StockMovement
    {
        $prev = (float) $item->stock_qty;
        $new = max(0, $prev - $qty);

        $item->update(['stock_qty' => $new]);

        return StockMovement::create([
            'inventory_item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_SPOILAGE_WASTE,
            'quantity' => -$qty,
            'previous_stock' => $prev,
            'resulting_stock' => $new,
            'reference_note' => "Spoilage/Wastage: {$reason}",
            'logged_by' => $staff?->id,
        ]);
    }

    /**
     * Accurately check if an inventory item represents printable paper sheets.
     */
    protected function isPaperItem(InventoryItem $item): bool
    {
        $cat = strtolower($item->category ?? '');
        $name = strtolower($item->name ?? '');

        if (str_contains($name, 'leatherette') || str_contains($name, 'chipboard') || str_contains($name, 'foil') || str_contains($name, 'glue') || str_contains($name, 'board')) {
            return false;
        }

        return $cat === 'paper' || str_contains($name, 'paper') || str_contains($name, 'copier') || str_contains($name, 'gsm');
    }
}

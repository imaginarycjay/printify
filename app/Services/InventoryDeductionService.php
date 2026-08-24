<?php

namespace App\Services;

use App\Models\DocumentPrintingConfig;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
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

        DB::transaction(function () use ($order, $staff, &$deductions) {
            if ($order->service_key === 'document_printing') {
                $this->deductForDocumentPrinting($order, $staff, $deductions);
            } else {
                $this->deductForThesisBinding($order, $staff, $deductions);
            }
        });

        return $deductions;
    }

    /**
     * Handles BOM deduction for Document Printing orders.
     *
     * @param  array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>  $deductions
     */
    protected function deductForDocumentPrinting(Order $order, ?User $staff, array &$deductions): void
    {
        $shop = $order->printShop;
        if (! $shop) {
            return;
        }

        $config = DocumentPrintingConfig::where('print_shop_id', $shop->id)->first();
        if ($config && ! $config->auto_deduct_inventory) {
            return;
        }

        foreach ($order->items as $orderItem) {
            $copies = max(1, $orderItem->copies_count);
            $physicalSheets = $orderItem->getPhysicalSheetsCount();
            $paperSize = strtolower($orderItem->paper_size);

            // 1. Deduct Paper Stock
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

            // Fallback paper item lookup
            if (! $paperItem) {
                $paperItem = $shop->inventoryItems()
                    ->where(function ($q) use ($paperSize) {
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

            if ($paperItem && $physicalSheets > 0) {
                $deductions[] = $this->applyDeduction($paperItem, (float) $physicalSheets, "Order {$order->order_number} (Doc Print {$orderItem->paper_size} {$orderItem->getPrintSides()})", $staff);
            }

            // 2. Deduct Finishing Items (Ring Binding, Folders, Acetate)
            $finishing = $orderItem->getFinishingType();
            if ($finishing === 'ring_bind') {
                // Ring Spine
                $ringItem = ($config && $config->bom_ring_spine_item_id) ? $config->ringSpineItem : null;
                if (! $ringItem) {
                    $ringItem = $shop->inventoryItems()->where('name', 'like', '%Ring%')->orWhere('name', 'like', '%Comb%')->first();
                }
                if ($ringItem) {
                    $deductions[] = $this->applyDeduction($ringItem, (float) $copies, "Order {$order->order_number} (Plastic Ring Comb)", $staff);
                }

                // PVC Acetate Covers (2 per copy: front and back)
                $acetateItem = ($config && $config->bom_pvc_acetate_item_id) ? $config->pvcAcetateItem : null;
                if (! $acetateItem) {
                    $acetateItem = $shop->inventoryItems()->where('name', 'like', '%Acetate%')->orWhere('name', 'like', '%PVC%')->first();
                }
                if ($acetateItem) {
                    $deductions[] = $this->applyDeduction($acetateItem, (float) ($copies * 2), "Order {$order->order_number} (Clear PVC Acetate Covers)", $staff);
                }

                // Back Cover Board
                $boardItem = ($config && $config->bom_back_cover_item_id) ? $config->backCoverItem : null;
                if (! $boardItem) {
                    $boardItem = $shop->inventoryItems()->where('name', 'like', '%Morocco%')->orWhere('name', 'like', '%Board%')->first();
                }
                if ($boardItem) {
                    $deductions[] = $this->applyDeduction($boardItem, (float) $copies, "Order {$order->order_number} (Morocco Back Board)", $staff);
                }
            } elseif ($finishing === 'folder') {
                $folderItem = $shop->inventoryItems()->where('name', 'like', '%Folder%')->first();
                if ($folderItem) {
                    $deductions[] = $this->applyDeduction($folderItem, (float) $copies, "Order {$order->order_number} (Sliding Folder)", $staff);
                }
            }
        }
    }

    /**
     * Handles BOM deduction for Thesis Binding orders.
     *
     * @param  array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>  $deductions
     */
    protected function deductForThesisBinding(Order $order, ?User $staff, array &$deductions): void
    {
        $shop = $order->printShop;
        if (! $shop) {
            return;
        }

        $config = ThesisBindingConfig::where('print_shop_id', $shop->id)->first();
        if ($config && ! $config->auto_deduct_inventory) {
            return;
        }

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

                    $deductions[] = $this->applyDeduction($inventoryItem, (float) $consumedQty, "Order {$order->order_number} ({$orderItem->binding_type} ".($isCoverOnly ? 'Cover Only' : 'Full Package').')', $staff);
                }
            } else {
                // Fallback: Smart deduction from shop inventory matching keywords
                $this->fallbackDeduction($order, $orderItem, $staff, $deductions);
            }
        }
    }

    /**
     * Fallback smart deduction when specific BOM recipes have not been explicitly linked.
     *
     * @param  array<int, array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}>  $deductions
     */
    protected function fallbackDeduction(Order $order, OrderItem $orderItem, ?User $staff, array &$deductions): void
    {
        $shop = $order->printShop;
        if (! $shop) {
            return;
        }

        $copies = max(1, $orderItem->copies_count);
        $isCoverOnly = $orderItem->isCoverOnly();
        $totalPages = $orderItem->total_pages_count ?? 0;

        // 1. Bond Paper (Deduct 0 if Cover Only)
        if (! $isCoverOnly && $totalPages > 0) {
            $paperItem = $shop->inventoryItems()->where('name', 'like', '%Bond Paper%')->orWhere('name', 'like', '%Paper%')->first();
            if ($paperItem) {
                $paperQty = (float) ($totalPages * $copies);
                $deductions[] = $this->applyDeduction($paperItem, $paperQty, "Order {$order->order_number} (Manuscript Pages)", $staff);
            }
        }

        // 2. Hardbound Materials (Chipboard, Leatherette, Gold Foil)
        if ($orderItem->binding_type === 'hardbound') {
            $boardItem = $shop->inventoryItems()->where('name', 'like', '%Chipboard%')->orWhere('name', 'like', '%Board%')->first();
            if ($boardItem) {
                $deductions[] = $this->applyDeduction($boardItem, (float) $copies, "Order {$order->order_number} (Hardbound Chipboard)", $staff);
            }

            $leatherItem = $shop->inventoryItems()->where('name', 'like', '%Leatherette%')->orWhere('name', 'like', '%Leather%')->first();
            if ($leatherItem) {
                $deductions[] = $this->applyDeduction($leatherItem, (float) $copies, "Order {$order->order_number} (Leatherette Cover)", $staff);
            }

            $foilItem = $shop->inventoryItems()->where('name', 'like', '%Foil%')->first();
            if ($foilItem) {
                $deductions[] = $this->applyDeduction($foilItem, (float) $copies, "Order {$order->order_number} (Foil Stamping)", $staff);
            }
        }
    }

    /**
     * Apply stock deduction and log StockMovement.
     *
     * @return array{item_name: string, quantity_deducted: float, unit: string, remaining_stock: float}
     */
    protected function applyDeduction(
        InventoryItem $inventoryItem,
        float $consumedQty,
        string $referenceNote,
        ?User $staff
    ): array {
        $prevStock = (float) $inventoryItem->stock_qty;
        $newStock = (float) max(0, $prevStock - $consumedQty);

        $inventoryItem->update(['stock_qty' => $newStock]);

        StockMovement::create([
            'inventory_item_id' => $inventoryItem->id,
            'movement_type' => StockMovement::TYPE_PRODUCTION_DEDUCTION,
            'quantity' => -$consumedQty,
            'previous_stock' => $prevStock,
            'resulting_stock' => $newStock,
            'reference_note' => $referenceNote,
            'logged_by' => $staff?->id,
        ]);

        return [
            'item_name' => $inventoryItem->name,
            'quantity_deducted' => $consumedQty,
            'unit' => $inventoryItem->unit,
            'remaining_stock' => $newStock,
        ];
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
     * Record material wastage or spoilage.
     */
    public function recordSpoilage(
        InventoryItem $item,
        float $wastedQty,
        string $reason,
        ?User $staff = null
    ): StockMovement {
        $prevStock = (float) $item->stock_qty;
        $newStock = max(0, $prevStock - $wastedQty);

        $item->update(['stock_qty' => $newStock]);

        return StockMovement::create([
            'inventory_item_id' => $item->id,
            'movement_type' => StockMovement::TYPE_SPOILAGE_WASTE,
            'quantity' => -$wastedQty,
            'previous_stock' => $prevStock,
            'resulting_stock' => $newStock,
            'reference_note' => "Spoilage/Waste: {$reason}",
            'logged_by' => $staff?->id,
        ]);
    }
}

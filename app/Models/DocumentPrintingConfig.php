<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $print_shop_id
 * @property float $page_price_bw_short
 * @property float $page_price_bw_a4
 * @property float $page_price_bw_long
 * @property float $page_price_color_short
 * @property float $page_price_color_a4
 * @property float $page_price_color_long
 * @property float $paper_stock_70gsm_price
 * @property float $paper_stock_80gsm_price
 * @property float $paper_stock_100gsm_price
 * @property int $duplex_discount_percent
 * @property bool $allow_staple
 * @property float $staple_price
 * @property bool $allow_folder_fastener
 * @property float $folder_fastener_price
 * @property bool $allow_ring_binding
 * @property float $ring_bind_base_price
 * @property bool $allow_booklet_staple
 * @property float $booklet_staple_price
 * @property bool $allow_rush_orders
 * @property float $rush_fee_amount
 * @property bool $auto_deduct_inventory
 * @property int|null $bom_short_paper_item_id
 * @property int|null $bom_a4_paper_item_id
 * @property int|null $bom_long_paper_item_id
 * @property int|null $bom_ring_spine_item_id
 * @property int|null $bom_pvc_acetate_item_id
 * @property int|null $bom_back_cover_item_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'print_shop_id',
    'page_price_bw_short',
    'page_price_bw_a4',
    'page_price_bw_long',
    'page_price_color_short',
    'page_price_color_a4',
    'page_price_color_long',
    'paper_stock_70gsm_price',
    'paper_stock_80gsm_price',
    'paper_stock_100gsm_price',
    'duplex_discount_percent',
    'allow_staple',
    'staple_price',
    'allow_folder_fastener',
    'folder_fastener_price',
    'allow_ring_binding',
    'ring_bind_base_price',
    'allow_booklet_staple',
    'booklet_staple_price',
    'allow_rush_orders',
    'rush_fee_amount',
    'auto_deduct_inventory',
    'bom_short_paper_item_id',
    'bom_a4_paper_item_id',
    'bom_long_paper_item_id',
    'bom_ring_spine_item_id',
    'bom_pvc_acetate_item_id',
    'bom_back_cover_item_id',
])]
class DocumentPrintingConfig extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_price_bw_short' => 'float',
            'page_price_bw_a4' => 'float',
            'page_price_bw_long' => 'float',
            'page_price_color_short' => 'float',
            'page_price_color_a4' => 'float',
            'page_price_color_long' => 'float',
            'paper_stock_70gsm_price' => 'float',
            'paper_stock_80gsm_price' => 'float',
            'paper_stock_100gsm_price' => 'float',
            'duplex_discount_percent' => 'integer',
            'allow_staple' => 'boolean',
            'staple_price' => 'float',
            'allow_folder_fastener' => 'boolean',
            'folder_fastener_price' => 'float',
            'allow_ring_binding' => 'boolean',
            'ring_bind_base_price' => 'float',
            'allow_booklet_staple' => 'boolean',
            'booklet_staple_price' => 'float',
            'allow_rush_orders' => 'boolean',
            'rush_fee_amount' => 'float',
            'auto_deduct_inventory' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function shortPaperItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'bom_short_paper_item_id');
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function a4PaperItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'bom_a4_paper_item_id');
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function longPaperItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'bom_long_paper_item_id');
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function ringSpineItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'bom_ring_spine_item_id');
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function pvcAcetateItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'bom_pvc_acetate_item_id');
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function backCoverItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'bom_back_cover_item_id');
    }

    /**
     * Helper to get B&W rate for a given paper size.
     */
    public function getBwRate(string $size): float
    {
        return match (strtolower($size)) {
            'a4' => $this->page_price_bw_a4,
            'long', 'legal', 'folio' => $this->page_price_bw_long,
            default => $this->page_price_bw_short,
        };
    }

    /**
     * Helper to get Colored rate for a given paper size.
     */
    public function getColorRate(string $size): float
    {
        return match (strtolower($size)) {
            'a4' => $this->page_price_color_a4,
            'long', 'legal', 'folio' => $this->page_price_color_long,
            default => $this->page_price_color_short,
        };
    }

    /**
     * Helper to get paper stock extra fee.
     */
    public function getPaperStockFee(string $stock): float
    {
        return match (strtolower($stock)) {
            '80gsm', '80' => $this->paper_stock_80gsm_price,
            '100gsm', '100', 'specialty' => $this->paper_stock_100gsm_price,
            default => $this->paper_stock_70gsm_price,
        };
    }
}

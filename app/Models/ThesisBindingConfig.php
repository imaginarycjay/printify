<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $print_shop_id
 * @property bool $is_active
 * @property float $hardbound_base_price
 * @property float $softbound_base_price
 * @property float $page_price_bw
 * @property float $page_price_color
 * @property float $rush_fee
 * @property array<int, string>|null $cover_colors
 * @property array<int, string>|null $foil_colors
 * @property array<int, string>|null $paper_sizes
 * @property bool $auto_deduct_inventory
 * @property int $daily_production_quota
 * @property int $standard_lead_time_days
 * @property int $rush_lead_time_days
 * @property bool $require_pdf_upload
 * @property array<int, string>|null $custom_cover_fields
 * @property bool $allow_customer_supplied_paper
 * @property float $hardbound_cover_only_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'print_shop_id',
    'is_active',
    'hardbound_base_price',
    'softbound_base_price',
    'allow_customer_supplied_paper',
    'hardbound_cover_only_price',
    'page_price_bw',
    'page_price_color',
    'rush_fee',
    'cover_colors',
    'foil_colors',
    'paper_sizes',
    'auto_deduct_inventory',
    'daily_production_quota',
    'standard_lead_time_days',
    'rush_lead_time_days',
    'require_pdf_upload',
    'custom_cover_fields',
])]
class ThesisBindingConfig extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hardbound_base_price' => 'float',
            'softbound_base_price' => 'float',
            'allow_customer_supplied_paper' => 'boolean',
            'hardbound_cover_only_price' => 'float',
            'page_price_bw' => 'float',
            'page_price_color' => 'float',
            'rush_fee' => 'float',
            'cover_colors' => 'array',
            'foil_colors' => 'array',
            'paper_sizes' => 'array',
            'auto_deduct_inventory' => 'boolean',
            'daily_production_quota' => 'integer',
            'standard_lead_time_days' => 'integer',
            'rush_lead_time_days' => 'integer',
            'require_pdf_upload' => 'boolean',
            'custom_cover_fields' => 'array',
        ];
    }

    /**
     * Get the print shop that owns this thesis binding configuration.
     *
     * @return BelongsTo<PrintShop, $this>
     */
    public function printShop(): BelongsTo
    {
        return $this->belongsTo(PrintShop::class);
    }

    /**
     * Get the Bill of Materials (BOM) items for this configuration.
     *
     * @return HasMany<ThesisBindingBomItem, $this>
     */
    public function bomItems(): HasMany
    {
        return $this->hasMany(ThesisBindingBomItem::class);
    }

    /**
     * Default cover colors if none set.
     *
     * @return array<int, string>
     */
    public static function defaultCoverColors(): array
    {
        return ['Maroon', 'Dark Blue', 'Black', 'Green'];
    }

    /**
     * Default foil text colors if none set.
     *
     * @return array<int, string>
     */
    public static function defaultFoilColors(): array
    {
        return ['Gold', 'Silver'];
    }

    /**
     * Default paper sizes if none set.
     *
     * @return array<int, string>
     */
    public static function defaultPaperSizes(): array
    {
        return ['A4', 'Letter (Short)', 'Legal (Long)'];
    }

    /**
     * Default custom cover fields if none set.
     *
     * @return array<int, string>
     */
    public static function defaultCustomCoverFields(): array
    {
        return ['Thesis Title', 'Name of Researchers', 'Degree / Course', 'School Year'];
    }
}

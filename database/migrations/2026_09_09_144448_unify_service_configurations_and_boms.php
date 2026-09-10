<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add display_order and settings JSON columns to shop_services
        if (Schema::hasTable('shop_services')) {
            Schema::table('shop_services', function (Blueprint $table) {
                if (! Schema::hasColumn('shop_services', 'display_order')) {
                    $table->integer('display_order')->default(0);
                }
                if (! Schema::hasColumn('shop_services', 'settings')) {
                    $table->json('settings')->nullable();
                }
            });
        }

        // 2. Create unified service_boms table
        if (! Schema::hasTable('service_boms')) {
            Schema::create('service_boms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('print_shop_id')->constrained('print_shops')->cascadeOnDelete();
                $table->string('service_key')->index(); // 'thesis_binding', 'document_printing', etc.
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
                $table->string('component_name'); // 'paper_short', 'ring_spine', 'hardbound_board', etc.
                $table->string('usage_type')->default('per_copy'); // 'per_page', 'per_sheet', 'per_copy', 'fixed'
                $table->decimal('usage_qty', 8, 2)->default(1.00);
                $table->string('unit')->default('pcs');
                $table->json('conditions')->nullable(); // e.g. {"binding_type": "hardbound"} or {"paper_size": "a4"}
                $table->timestamps();
            });
        }

        // 3. Add specifications JSON column to order_items
        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'specifications')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->json('specifications')->nullable()->after('total_pages_count');
            });
        }

        // 4. Backfill data into unified tables where possible
        $this->backfillUnifiedData();
    }

    /**
     * Backfill legacy configurations into the unified schema.
     */
    protected function backfillUnifiedData(): void
    {
        // Backfill ThesisBindingConfig -> shop_services.settings
        if (Schema::hasTable('thesis_binding_configs')) {
            $configs = DB::table('thesis_binding_configs')->get();
            foreach ($configs as $cfg) {
                $settings = [
                    'hardbound_base_price' => (float) $cfg->hardbound_base_price,
                    'softbound_base_price' => (float) $cfg->softbound_base_price,
                    'allow_customer_supplied_paper' => (bool) $cfg->allow_customer_supplied_paper,
                    'hardbound_cover_only_price' => (float) $cfg->hardbound_cover_only_price,
                    'page_price_bw' => (float) $cfg->page_price_bw,
                    'page_price_color' => (float) $cfg->page_price_color,
                    'rush_fee' => (float) $cfg->rush_fee,
                    'cover_colors' => json_decode((string) $cfg->cover_colors, true) ?: [],
                    'foil_colors' => json_decode((string) $cfg->foil_colors, true) ?: [],
                    'paper_sizes' => json_decode((string) $cfg->paper_sizes, true) ?: [],
                    'auto_deduct_inventory' => (bool) $cfg->auto_deduct_inventory,
                    'daily_production_quota' => (int) $cfg->daily_production_quota,
                    'standard_lead_time_days' => (int) $cfg->standard_lead_time_days,
                    'rush_lead_time_days' => (int) $cfg->rush_lead_time_days,
                    'require_pdf_upload' => (bool) $cfg->require_pdf_upload,
                    'custom_cover_fields' => json_decode((string) $cfg->custom_cover_fields, true) ?: [],
                ];

                DB::table('shop_services')
                    ->where('print_shop_id', $cfg->print_shop_id)
                    ->where('service_key', 'thesis_binding')
                    ->update(['settings' => json_encode($settings)]);
            }
        }

        // Backfill DocumentPrintingConfig -> shop_services.settings and service_boms
        if (Schema::hasTable('document_printing_configs')) {
            $docConfigs = DB::table('document_printing_configs')->get();
            foreach ($docConfigs as $docCfg) {
                $docSettings = [
                    'page_price_bw_short' => (float) $docCfg->page_price_bw_short,
                    'page_price_bw_a4' => (float) $docCfg->page_price_bw_a4,
                    'page_price_bw_long' => (float) $docCfg->page_price_bw_long,
                    'page_price_color_short' => (float) $docCfg->page_price_color_short,
                    'page_price_color_a4' => (float) $docCfg->page_price_color_a4,
                    'page_price_color_long' => (float) $docCfg->page_price_color_long,
                    'paper_stock_70gsm_price' => (float) $docCfg->paper_stock_70gsm_price,
                    'paper_stock_80gsm_price' => (float) $docCfg->paper_stock_80gsm_price,
                    'paper_stock_100gsm_price' => (float) $docCfg->paper_stock_100gsm_price,
                    'duplex_discount_percent' => (int) $docCfg->duplex_discount_percent,
                    'allow_staple' => (bool) $docCfg->allow_staple,
                    'staple_price' => (float) $docCfg->staple_price,
                    'allow_folder_fastener' => (bool) $docCfg->allow_folder_fastener,
                    'folder_fastener_price' => (float) $docCfg->folder_fastener_price,
                    'allow_ring_binding' => (bool) $docCfg->allow_ring_binding,
                    'ring_bind_base_price' => (float) $docCfg->ring_bind_base_price,
                    'allow_booklet_staple' => (bool) $docCfg->allow_booklet_staple,
                    'booklet_staple_price' => (float) $docCfg->booklet_staple_price,
                    'allow_rush_orders' => (bool) $docCfg->allow_rush_orders,
                    'rush_fee_amount' => (float) $docCfg->rush_fee_amount,
                    'auto_deduct_inventory' => (bool) $docCfg->auto_deduct_inventory,
                ];

                DB::table('shop_services')
                    ->where('print_shop_id', $docCfg->print_shop_id)
                    ->where('service_key', 'document_printing')
                    ->update(['settings' => json_encode($docSettings)]);

                // Migrate document printing BOM linkages into service_boms
                $bomMappings = [
                    ['col' => 'bom_short_paper_item_id', 'name' => 'Paper (Short / Letter)', 'type' => 'per_sheet', 'qty' => 1.0, 'unit' => 'sheets', 'cond' => ['paper_size' => 'short']],
                    ['col' => 'bom_a4_paper_item_id', 'name' => 'Paper (A4)', 'type' => 'per_sheet', 'qty' => 1.0, 'unit' => 'sheets', 'cond' => ['paper_size' => 'a4']],
                    ['col' => 'bom_long_paper_item_id', 'name' => 'Paper (Long / Legal)', 'type' => 'per_sheet', 'qty' => 1.0, 'unit' => 'sheets', 'cond' => ['paper_size' => 'long']],
                    ['col' => 'bom_ring_spine_item_id', 'name' => 'Plastic Ring Spine', 'type' => 'per_copy', 'qty' => 1.0, 'unit' => 'pcs', 'cond' => ['finishing_type' => 'ring_bind']],
                    ['col' => 'bom_pvc_acetate_item_id', 'name' => 'PVC Acetate Cover', 'type' => 'per_copy', 'qty' => 2.0, 'unit' => 'pcs', 'cond' => ['finishing_type' => 'ring_bind']],
                    ['col' => 'bom_back_cover_item_id', 'name' => 'Morocco Back Board', 'type' => 'per_copy', 'qty' => 1.0, 'unit' => 'pcs', 'cond' => ['finishing_type' => 'ring_bind']],
                ];

                foreach ($bomMappings as $bm) {
                    $itemId = $docCfg->{$bm['col']} ?? null;
                    if ($itemId) {
                        DB::table('service_boms')->insert([
                            'print_shop_id' => $docCfg->print_shop_id,
                            'service_key' => 'document_printing',
                            'inventory_item_id' => $itemId,
                            'component_name' => $bm['name'],
                            'usage_type' => $bm['type'],
                            'usage_qty' => $bm['qty'],
                            'unit' => $bm['unit'],
                            'conditions' => json_encode($bm['cond']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Backfill ThesisBindingBomItem -> service_boms
        if (Schema::hasTable('thesis_binding_bom_items') && Schema::hasTable('thesis_binding_configs')) {
            $thesisBoms = DB::table('thesis_binding_bom_items')
                ->join('thesis_binding_configs', 'thesis_binding_bom_items.thesis_binding_config_id', '=', 'thesis_binding_configs.id')
                ->select(
                    'thesis_binding_configs.print_shop_id',
                    'thesis_binding_bom_items.inventory_item_id',
                    'thesis_binding_bom_items.binding_type',
                    'thesis_binding_bom_items.usage_qty',
                    'thesis_binding_bom_items.unit'
                )
                ->get();

            foreach ($thesisBoms as $tb) {
                DB::table('service_boms')->insert([
                    'print_shop_id' => $tb->print_shop_id,
                    'service_key' => 'thesis_binding',
                    'inventory_item_id' => $tb->inventory_item_id,
                    'component_name' => 'Thesis '.ucfirst((string) $tb->binding_type).' Material',
                    'usage_type' => 'per_copy',
                    'usage_qty' => (float) $tb->usage_qty,
                    'unit' => $tb->unit,
                    'conditions' => json_encode(['binding_type' => $tb->binding_type]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_boms');

        if (Schema::hasTable('shop_services') && Schema::hasColumn('shop_services', 'settings')) {
            Schema::table('shop_services', function (Blueprint $table) {
                $table->dropColumn('settings');
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'specifications')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('specifications');
            });
        }
    }
};

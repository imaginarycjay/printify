<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_printing_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_shop_id')->constrained()->cascadeOnDelete();

            // Black & White Rates per page
            $table->decimal('page_price_bw_short', 8, 2)->default(1.50);
            $table->decimal('page_price_bw_a4', 8, 2)->default(1.50);
            $table->decimal('page_price_bw_long', 8, 2)->default(2.00);

            // Colored Rates per page
            $table->decimal('page_price_color_short', 8, 2)->default(5.00);
            $table->decimal('page_price_color_a4', 8, 2)->default(5.00);
            $table->decimal('page_price_color_long', 8, 2)->default(6.00);

            // Paper Stock Add-on Rates
            $table->decimal('paper_stock_70gsm_price', 8, 2)->default(0.00);
            $table->decimal('paper_stock_80gsm_price', 8, 2)->default(0.50);
            $table->decimal('paper_stock_100gsm_price', 8, 2)->default(1.50);

            // Duplex (Back-to-Back) Discount Percentage (0-100)
            $table->unsignedTinyInteger('duplex_discount_percent')->default(10);

            // Finishing & Binding Rates
            $table->boolean('allow_staple')->default(true);
            $table->decimal('staple_price', 8, 2)->default(2.00);

            $table->boolean('allow_folder_fastener')->default(true);
            $table->decimal('folder_fastener_price', 8, 2)->default(15.00);

            $table->boolean('allow_ring_binding')->default(true);
            $table->decimal('ring_bind_base_price', 8, 2)->default(45.00);

            $table->boolean('allow_booklet_staple')->default(true);
            $table->decimal('booklet_staple_price', 8, 2)->default(20.00);

            // Rush Order Configuration
            $table->boolean('allow_rush_orders')->default(true);
            $table->decimal('rush_fee_amount', 8, 2)->default(50.00);

            // Automated Inventory BOM Deduction Flags
            $table->boolean('auto_deduct_inventory')->default(true);

            // BOM Inventory Item Foreign Keys
            $table->foreignId('bom_short_paper_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('bom_a4_paper_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('bom_long_paper_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('bom_ring_spine_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('bom_pvc_acetate_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->foreignId('bom_back_cover_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_printing_configs');
    }
};

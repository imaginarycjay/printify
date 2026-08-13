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
        Schema::create('thesis_binding_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_shop_id')->unique()->constrained('print_shops')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);

            // Pricing Configuration
            $table->decimal('hardbound_base_price', 8, 2)->default(350.00);
            $table->decimal('softbound_base_price', 8, 2)->default(150.00);
            $table->decimal('page_price_bw', 8, 2)->default(1.50);
            $table->decimal('page_price_color', 8, 2)->default(5.00);
            $table->decimal('rush_fee', 8, 2)->default(150.00);

            // Variants (JSON)
            $table->json('cover_colors')->nullable();
            $table->json('foil_colors')->nullable();
            $table->json('paper_sizes')->nullable();

            // BOM / Inventory Rules
            $table->boolean('auto_deduct_inventory')->default(true);

            // Production & Scheduling Limits
            $table->integer('daily_production_quota')->default(20);
            $table->integer('standard_lead_time_days')->default(4);
            $table->integer('rush_lead_time_days')->default(1);

            // Customer Form Requirements
            $table->boolean('require_pdf_upload')->default(true);
            $table->json('custom_cover_fields')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thesis_binding_configs');
    }
};

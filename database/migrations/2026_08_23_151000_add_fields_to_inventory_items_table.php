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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('item_type')->default('raw_material')->after('category');
            $table->string('service_tag')->nullable()->after('item_type');
            $table->decimal('unit_cost', 10, 2)->default(0.00)->after('reorder_level');
            $table->decimal('selling_price', 10, 2)->nullable()->after('unit_cost');
            $table->string('supplier_name')->nullable()->after('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'service_tag', 'unit_cost', 'selling_price', 'supplier_name']);
        });
    }
};

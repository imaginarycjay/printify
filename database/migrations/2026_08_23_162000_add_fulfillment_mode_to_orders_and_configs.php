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
        Schema::table('thesis_binding_configs', function (Blueprint $table) {
            $table->boolean('allow_customer_supplied_paper')->default(true)->after('softbound_base_price');
            $table->decimal('hardbound_cover_only_price', 10, 2)->default(300.00)->after('allow_customer_supplied_paper');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('fulfillment_type')->default('full_package')->after('binding_type'); // 'full_package', 'cover_only'
            $table->boolean('is_paper_received')->default(false)->after('fulfillment_type');
            $table->decimal('estimated_spine_thickness_mm', 5, 2)->default(0.00)->after('is_paper_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thesis_binding_configs', function (Blueprint $table) {
            $table->dropColumn(['allow_customer_supplied_paper', 'hardbound_cover_only_price']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_type', 'is_paper_received', 'estimated_spine_thickness_mm']);
        });
    }
};

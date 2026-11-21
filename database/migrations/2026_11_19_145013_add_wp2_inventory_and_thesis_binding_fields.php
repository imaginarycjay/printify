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
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->decimal('reserved_qty', 10, 2)->default(0.00);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
        });

        Schema::table('thesis_binding_configs', function (Blueprint $table): void {
            $table->unsignedInteger('max_binding_sheets')->default(250);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thesis_binding_configs', function (Blueprint $table): void {
            $table->dropColumn('max_binding_sheets');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_item_id');
        });

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->dropColumn('reserved_qty');
        });
    }
};

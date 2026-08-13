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
        Schema::create('thesis_binding_bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thesis_binding_config_id')->constrained('thesis_binding_configs')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('binding_type')->default('hardbound'); // hardbound, softbound, both
            $table->decimal('usage_qty', 8, 2)->default(1.00);
            $table->string('unit')->default('pcs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thesis_binding_bom_items');
    }
};

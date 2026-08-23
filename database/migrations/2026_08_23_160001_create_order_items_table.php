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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('binding_type')->default('hardbound');
            $table->integer('bw_pages_count')->default(0);
            $table->integer('color_pages_count')->default(0);
            $table->integer('total_pages_count')->default(0);
            $table->string('cover_color')->nullable();
            $table->string('foil_color')->nullable();
            $table->string('paper_size')->default('A4');
            $table->integer('copies_count')->default(1);
            $table->json('custom_fields_data')->nullable();
            $table->json('selected_addons')->nullable();
            $table->string('document_file_path')->nullable();
            $table->string('document_original_name')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

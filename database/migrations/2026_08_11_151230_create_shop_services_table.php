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
        Schema::create('shop_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_shop_id')->constrained('print_shops')->cascadeOnDelete();
            $table->string('service_key');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['print_shop_id', 'service_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_services');
    }
};

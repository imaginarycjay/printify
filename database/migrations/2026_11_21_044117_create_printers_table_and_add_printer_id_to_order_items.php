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
        Schema::create('printers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('print_shop_id')->constrained('print_shops')->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('supported_sizes')->nullable();
            $table->string('status', 30)->default('online');
            $table->boolean('is_wide_format')->default(false);
            $table->timestamps();

            $table->index(['print_shop_id', 'status']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('printer_id')->nullable()->constrained('printers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('printer_id');
        });

        Schema::dropIfExists('printers');
    }
};

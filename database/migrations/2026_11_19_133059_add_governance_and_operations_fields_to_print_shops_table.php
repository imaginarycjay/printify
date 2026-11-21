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
        Schema::table('print_shops', function (Blueprint $table): void {
            $table->unsignedInteger('file_retention_days')->nullable();
            $table->unsignedInteger('busy_threshold')->default(5);
            $table->unsignedInteger('at_capacity_threshold')->default(10);
            $table->string('operating_hours', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_shops', function (Blueprint $table): void {
            $table->dropColumn([
                'file_retention_days',
                'busy_threshold',
                'at_capacity_threshold',
                'operating_hours',
            ]);
        });
    }
};

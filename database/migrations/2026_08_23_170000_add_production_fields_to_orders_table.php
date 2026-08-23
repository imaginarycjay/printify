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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('assigned_staff_id')->nullable()->after('payment_verified_by')->constrained('users')->nullOnDelete();
            $table->string('assigned_machine')->nullable()->after('assigned_staff_id');
            $table->string('production_stage')->default('queue')->after('assigned_machine'); // queue, printing, binding, quality_check, ready_for_pickup, completed
            $table->timestamp('production_started_at')->nullable()->after('production_stage');
            $table->timestamp('production_completed_at')->nullable()->after('production_started_at');
            $table->text('staff_notes')->nullable()->after('production_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_staff_id']);
            $table->dropColumn([
                'assigned_staff_id',
                'assigned_machine',
                'production_stage',
                'production_started_at',
                'production_completed_at',
                'staff_notes',
            ]);
        });
    }
};

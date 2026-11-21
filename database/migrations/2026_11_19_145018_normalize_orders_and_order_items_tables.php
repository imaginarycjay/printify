<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('service_key')->nullable()->default(null)->change();
            $table->string('tracking_code', 32)->nullable()->unique();
            $table->string('payment_option', 30)->default('full_100');
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('balance_due', 10, 2)->default(0.00);
            $table->decimal('refund_due', 10, 2)->default(0.00);
            $table->string('refund_status', 30)->default('none');
            $table->timestamp('completed_at')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('service_key', 60)->nullable()->index();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('production_stage', 40)->default('queue')->index();
            $table->string('prepress_status', 40)->default('pending');
            $table->text('prepress_rejection_reason')->nullable();
            $table->boolean('is_spec_locked')->default(false);
            $table->string('shelf_bin_code', 50)->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('file_purged_at')->nullable();
        });

        DB::table('order_items')
            ->whereNull('service_key')
            ->orderBy('id')
            ->chunkById(100, function ($items): void {
                $orderServices = DB::table('orders')
                    ->whereIn('id', $items->pluck('order_id')->unique())
                    ->pluck('service_key', 'id');

                foreach ($items as $item) {
                    $serviceKey = $orderServices[$item->order_id] ?? null;
                    if (is_string($serviceKey) && $serviceKey !== '') {
                        DB::table('order_items')
                            ->where('id', $item->id)
                            ->update(['service_key' => $serviceKey]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['service_key']);
            $table->dropIndex(['production_stage']);
            $table->dropConstrainedForeignId('assigned_staff_id');
            $table->dropColumn([
                'service_key',
                'production_stage',
                'prepress_status',
                'prepress_rejection_reason',
                'is_spec_locked',
                'shelf_bin_code',
                'ready_at',
                'claimed_at',
                'file_purged_at',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['tracking_code']);
            $table->dropColumn([
                'tracking_code',
                'payment_option',
                'amount_paid',
                'balance_due',
                'refund_due',
                'refund_status',
                'completed_at',
            ]);
        });

        DB::table('orders')->whereNull('service_key')->update(['service_key' => 'thesis_binding']);

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('service_key')->default('thesis_binding')->nullable(false)->change();
        });
    }
};

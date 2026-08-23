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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('print_shop_id')->constrained('print_shops')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('service_key')->default('thesis_binding');
            $table->string('order_status')->default('pending_payment'); // pending_payment, in_queue, in_production, quality_check, ready_for_pickup, completed, cancelled
            $table->string('payment_status')->default('pending_verification'); // unpaid, pending_verification, verified_paid, rejected
            $table->decimal('subtotal_amount', 10, 2)->default(0.00);
            $table->decimal('rush_fee_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->boolean('is_rush')->default(false);
            $table->date('target_completion_date')->nullable();
            $table->string('payment_proof_path')->nullable();
            $table->string('payment_reference_no')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

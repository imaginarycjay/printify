<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('print_shops')->select('user_id')->groupBy('user_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Resolve duplicate shops per owner before migrating.');
        }
        Schema::table('print_shops', function (Blueprint $table): void {
            $table->unique('user_id');
            $table->string('approval_status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->string('address')->nullable();
            $table->string('contact')->nullable();
            $table->text('description')->nullable();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('staff_shop_id')->nullable()->constrained('print_shops')->nullOnDelete();
        });
        Schema::table('shop_services', function (Blueprint $table): void {
            $table->unsignedInteger('capacity')->default(10);
            $table->text('pause_reason')->nullable();
            $table->timestamp('paused_until')->nullable();
        });
        Schema::create('staff_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('print_shop_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invitations');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('staff_shop_id');
        });
        Schema::table('shop_services', function (Blueprint $table): void {
            $table->dropColumn(['capacity', 'pause_reason', 'paused_until']);
        });
        Schema::table('print_shops', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
            $table->dropColumn(['approval_status', 'rejection_reason', 'address', 'contact', 'description']);
        });
    }
};

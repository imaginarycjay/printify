<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Printer;
use App\Models\PrintShop;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\AgingOperationalAlertNotification;
use App\Notifications\LowStockReorderNotification;
use Database\Seeders\PressQueueDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PressQueueDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_pressqueue_demo_seeder_populates_multi_tenant_data_idempotently(): void
    {
        Mail::fake();
        Storage::fake('local');

        $this->seed(PressQueueDemoSeeder::class);
        $this->seed(PressQueueDemoSeeder::class); // Idempotency check

        $admin = User::where('email', 'admin@pressqueue.test')->firstOrFail();
        $owner1 = User::where('email', 'owner@pressqueue.test')->firstOrFail();
        $owner2 = User::where('email', 'owner2@pressqueue.test')->firstOrFail();
        $owner3 = User::where('email', 'owner3@pressqueue.test')->firstOrFail();
        $owner4 = User::where('email', 'owner4@pressqueue.test')->firstOrFail();
        $customer1 = User::where('email', 'customer@pressqueue.test')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame(1, User::where('email', 'owner@pressqueue.test')->count());

        $shop1 = PrintShop::where('user_id', $owner1->id)->firstOrFail();
        $shop2 = PrintShop::where('user_id', $owner2->id)->firstOrFail();
        $shop3 = PrintShop::where('user_id', $owner3->id)->firstOrFail();
        $shop4 = PrintShop::where('user_id', $owner4->id)->firstOrFail();

        // Tenant workload & moderation states
        $this->assertSame('Normal', $shop1->workloadStatus());
        $this->assertSame('Busy', $shop2->workloadStatus());
        $this->assertSame('At capacity', $shop3->workloadStatus());
        $this->assertSame(PrintShop::STATUS_PENDING, $shop4->approval_status);

        // Tenant 3 temporarily paused thesis_binding service
        $pausedService = $shop3->services()->where('service_key', 'thesis_binding')->firstOrFail();
        $this->assertTrue($pausedService->isPaused());

        // Tenant 1 printer fleet & low-stock ROP
        $this->assertSame(3, Printer::where('print_shop_id', $shop1->id)->count());
        $this->assertSame(1, Printer::where('print_shop_id', $shop1->id)->where('status', Printer::STATUS_MAINTENANCE)->where('is_wide_format', true)->count());
        $this->assertSame(
            1,
            $owner1->notifications()->where('type', LowStockReorderNotification::class)->count(),
        );

        // Tenant 1 demo order states
        $order1 = Order::where('order_number', 'PQ-DEMO-1001')->firstOrFail();
        $this->assertSame(Order::STATUS_PARTIALLY_READY, $order1->order_status);
        $this->assertTrue($order1->items()->where('production_stage', OrderItem::STAGE_READY_FOR_PICKUP)->where('shelf_bin_code', 'A-04')->exists());

        $order2 = Order::where('order_number', 'PQ-DEMO-1002')->firstOrFail();
        $this->assertSame(Order::REFUND_MANUAL_PENDING, $order2->refund_status);
        $this->assertGreaterThan(0.0, (float) $order2->refund_due);

        $order3 = Order::where('order_number', 'PQ-DEMO-1003')->firstOrFail();
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order3->order_status);
        $this->assertTrue(Payment::where('order_id', $order3->id)->where('status', Payment::STATUS_PENDING)->exists());

        $order4 = Order::where('order_number', 'PQ-DEMO-1004')->firstOrFail();
        $item4Id = $order4->items()->firstOrFail()->id;
        $this->assertSame(2, StockMovement::where('order_item_id', $item4Id)->where('movement_type', StockMovement::TYPE_REWORK_SPOILAGE)->count());

        $order6 = Order::where('order_number', 'PQ-DEMO-1006')->firstOrFail();
        $this->assertSame(Order::STATUS_COMPLETED, $order6->order_status);
        $this->assertSame(Order::REFUND_SETTLED, $order6->refund_status);

        // Aging notifications & sample PDF files
        $this->assertTrue($owner1->notifications()->where('type', AgingOperationalAlertNotification::class)->exists());
        $this->assertTrue($customer1->notifications()->where('type', AgingOperationalAlertNotification::class)->exists());
        Storage::disk('local')->assertExists('orders/manuscripts/demo-thesis-manuscript.pdf');
        Storage::disk('local')->assertExists('orders/documents/demo-handout-pack.pdf');
    }
}

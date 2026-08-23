<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\PrintShop;
use App\Models\ThesisBindingConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerThesisOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $owner;

    protected PrintShop $shop;

    protected ThesisBindingConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        $this->customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->shop = PrintShop::create([
            'user_id' => $this->owner->id,
            'name' => 'Apex Print Hub',
            'is_setup_completed' => true,
        ]);

        $this->config = ThesisBindingConfig::create([
            'print_shop_id' => $this->shop->id,
            'is_active' => true,
            'hardbound_base_price' => 350.00,
            'softbound_base_price' => 150.00,
            'page_price_bw' => 1.50,
            'page_price_color' => 5.00,
            'rush_fee' => 150.00,
            'cover_colors' => ['Maroon', 'Dark Blue', 'Black'],
            'foil_colors' => ['Gold', 'Silver'],
            'paper_sizes' => ['A4', 'Letter (Short)'],
            'auto_deduct_inventory' => true,
            'daily_production_quota' => 20,
            'standard_lead_time_days' => 4,
            'rush_lead_time_days' => 1,
            'require_pdf_upload' => true,
            'custom_cover_fields' => ['Thesis Title', 'Name of Researchers', 'Degree / Course'],
        ]);

        $this->shop->inventoryItems()->create([
            'name' => 'Leatherette Cover Sheet (Maroon)',
            'sku' => 'MAT-LTH-MRN',
            'category' => 'Cover',
            'item_type' => InventoryItem::TYPE_RAW_MATERIAL,
            'stock_qty' => 50.0,
            'unit' => 'sheets',
            'reorder_level' => 10.0,
            'unit_cost' => 25.00,
        ]);
    }

    public function test_customer_can_access_thesis_order_wizard(): void
    {
        $response = $this->actingAs($this->customer)->get(route('customer.order-thesis'));
        $response->assertOk();
        $response->assertSee('Thesis Binding Order Portal');
    }

    public function test_price_calculation_computes_accurately(): void
    {
        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡thesis-order-wizard')
            ->set('binding_type', 'hardbound') // 350
            ->set('bw_pages', 80) // 80 * 1.50 = 120
            ->set('color_pages', 20) // 20 * 5.00 = 100
            ->set('copies_count', 2) // (350 + 120 + 100) * 2 = 1140
            ->set('is_rush', true) // +150
            ->assertSee('₱1,290.00'); // 1140 + 150 = 1290
    }

    public function test_customer_can_complete_thesis_order_and_payment(): void
    {
        Storage::fake('public');

        $dummyPdf = UploadedFile::fake()->create('thesis_final.pdf', 1024, 'application/pdf');
        $dummyReceipt = UploadedFile::fake()->create('gcash_receipt.png', 500, 'image/png');

        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡thesis-order-wizard')
            ->set('binding_type', 'hardbound')
            ->set('bw_pages', 50)
            ->set('color_pages', 10)
            ->set('paper_size', 'A4')
            ->set('cover_color', 'Maroon')
            ->set('foil_color', 'Gold')
            ->set('copies_count', 1)
            ->set('custom_fields_data.Thesis Title', 'Automated AI Print Management System')
            ->set('custom_fields_data.Name of Researchers', 'Juan Dela Cruz, Maria Clara')
            ->set('custom_fields_data.Degree / Course', 'BS Computer Science')
            ->set('manuscript_file', $dummyPdf)
            ->call('proceedToCheckout')
            ->assertSet('step', 2)
            ->set('payment_reference_no', '1002938475839')
            ->set('payment_proof_file', $dummyReceipt)
            ->call('submitOrder')
            ->assertSet('step', 3)
            ->assertSee('Thank You! Order Placed');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'print_shop_id' => $this->shop->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_PENDING_VERIFICATION,
            'payment_reference_no' => '1002938475839',
        ]);

        $this->assertDatabaseHas('order_items', [
            'binding_type' => 'hardbound',
            'bw_pages_count' => 50,
            'color_pages_count' => 10,
            'cover_color' => 'Maroon',
        ]);
    }

    public function test_customer_dashboard_displays_orders_and_live_stepper(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-2026-0001',
            'print_shop_id' => $this->shop->id,
            'customer_id' => $this->customer->id,
            'service_key' => 'thesis_binding',
            'order_status' => Order::STATUS_IN_PRODUCTION,
            'payment_status' => Order::PAYMENT_VERIFIED_PAID,
            'subtotal_amount' => 550.00,
            'rush_fee_amount' => 0.00,
            'total_amount' => 550.00,
            'is_rush' => false,
            'target_completion_date' => now()->addDays(4),
            'payment_reference_no' => '9988776655',
        ]);

        $order->items()->create([
            'binding_type' => 'hardbound',
            'bw_pages_count' => 80,
            'color_pages_count' => 10,
            'total_pages_count' => 90,
            'cover_color' => 'Maroon',
            'foil_color' => 'Gold',
            'paper_size' => 'A4',
            'copies_count' => 1,
            'unit_price' => 550.00,
            'total_price' => 550.00,
        ]);

        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡customer-dashboard')
            ->set('active_tab', 'orders')
            ->assertSee('ORD-2026-0001')
            ->assertSee('In production')
            ->assertSee('Printing')
            ->call('viewOrderDetails', $order->id)
            ->assertSee('Official Order Receipt');
    }

    public function test_softbound_order_greys_out_cover_colors_and_succeeds(): void
    {
        Storage::fake('public');

        $dummyPdf = UploadedFile::fake()->create('report_final.pdf', 512, 'application/pdf');

        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡thesis-order-wizard')
            ->set('binding_type', 'softbound')
            ->assertSee('Not Applicable')
            ->assertSee('transparent clear PVC acetate front sheet')
            ->set('bw_pages', 30)
            ->set('color_pages', 5)
            ->set('paper_size', 'A4')
            ->set('manuscript_file', $dummyPdf)
            ->call('proceedToCheckout')
            ->assertSet('step', 2)
            ->set('payment_reference_no', '8877665544332')
            ->call('submitOrder')
            ->assertSet('step', 3);

        $this->assertDatabaseHas('order_items', [
            'binding_type' => 'softbound',
            'cover_color' => 'Clear PVC / Standard Cardstock',
            'foil_color' => null,
        ]);
    }

    public function test_customer_can_place_cover_only_binding_order_with_zero_print_cost(): void
    {
        Storage::fake('public');

        $dummyRefPdf = UploadedFile::fake()->create('digital_reference.pdf', 512, 'application/pdf');

        Livewire::actingAs($this->customer)
            ->test('pages::customer.⚡thesis-order-wizard')
            ->set('fulfillment_type', 'cover_only')
            ->set('preprinted_total_pages', 150) // ~15.0mm spine
            ->set('paper_size', 'A4')
            ->set('cover_color', 'Maroon')
            ->set('foil_color', 'Gold')
            ->set('copies_count', 2)
            ->set('custom_fields_data.Thesis Title', 'Pre-printed Paper Binding Research')
            ->set('custom_fields_data.Name of Researchers', 'Student Researcher')
            ->set('custom_fields_data.Degree / Course', 'BS Information Technology')
            ->set('manuscript_file', $dummyRefPdf)
            ->assertSee('₱0.00 (Supplied)')
            ->assertSee('~15 mm')
            ->call('proceedToCheckout')
            ->assertSet('step', 2)
            ->set('payment_reference_no', 'GCASH-COVER-ONLY-12345')
            ->call('submitOrder')
            ->assertSet('step', 3)
            ->assertSee('Cover & Binding Only (Dala ang Papel)')
            ->assertSee('Drop off your physical paper');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'payment_reference_no' => 'GCASH-COVER-ONLY-12345',
            'subtotal_amount' => 600.00, // 300 base * 2 copies
        ]);

        $this->assertDatabaseHas('order_items', [
            'fulfillment_type' => 'cover_only',
            'is_paper_received' => false,
            'bw_pages_count' => 0,
            'color_pages_count' => 0,
            'total_pages_count' => 150,
            'estimated_spine_thickness_mm' => 15.0,
        ]);
    }

    public function test_owner_can_configure_cover_only_pricing_and_availability(): void
    {
        Livewire::actingAs($this->owner)
            ->test('pages::owner.⚡thesis-binding')
            ->set('allow_customer_supplied_paper', true)
            ->set('hardbound_cover_only_price', 280.00)
            ->call('savePricing')
            ->assertSee('General Setup & Pricing configuration saved successfully!');

        $this->assertDatabaseHas('thesis_binding_configs', [
            'print_shop_id' => $this->shop->id,
            'allow_customer_supplied_paper' => true,
            'hardbound_cover_only_price' => 280.00,
        ]);
    }
}

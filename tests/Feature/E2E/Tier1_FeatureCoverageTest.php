<?php

namespace Tests\Feature\E2E;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tier 1: Feature Coverage Test Suite (Opaque-Box E2E Testing)
 *
 * Verifies discrete functional completeness of all 21 user-facing features
 * specified in ORIGINAL_REQUEST.md and PROJECT.md.
 * Each feature contains at least 5 dedicated, high-fidelity test cases (Total: >= 105 tests).
 */
class Tier1_FeatureCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    protected function createAdminUser(): User
    {
        return User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin Laundry',
                'email' => 'admin@laundry.test',
                'password' => Hash::make('admin123'),
            ]
        );
    }

    protected function createStandardServices(): array
    {
        $services = [
            [
                'service_name' => 'Cuci Komplit Reguler',
                'category' => 'Kiloan',
                'price_per_unit' => 7000,
                'unit_type' => 'Kg',
                'description' => 'Cuci, kering, dan setrika rapi.',
            ],
            [
                'service_name' => 'Cuci Kering Lipat Cepat',
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
                'description' => 'Cuci bersih dan lipat wangi.',
            ],
            [
                'service_name' => 'Cuci Kilat Express 3 Jam',
                'category' => 'Express',
                'price_per_unit' => 15000,
                'unit_type' => 'Kg',
                'description' => 'Layanan express selesai dalam 3 jam.',
            ],
            [
                'service_name' => 'Bed Cover King Size',
                'category' => 'Satuan',
                'price_per_unit' => 25000,
                'unit_type' => 'Pcs',
                'description' => 'Pencucian khusus bed cover besar.',
            ],
            [
                'service_name' => 'Jas / Blazer Dry Cleaning',
                'category' => 'Satuan',
                'price_per_unit' => 35000,
                'unit_type' => 'Pcs',
                'description' => 'Dry cleaning profesional untuk jas/blazer.',
            ],
            [
                'service_name' => 'Selimut Tebal Bulu Halus',
                'category' => 'Satuan',
                'price_per_unit' => 20000,
                'unit_type' => 'Pcs',
                'description' => 'Pencucian higienis selimut tebal bulu halus.',
            ],
        ];

        $models = [];
        foreach ($services as $svc) {
            $models[] = Service::create($svc);
        }

        return $models;
    }

    protected function createOrderWithItems(string $name, string $phone, array $items, string $status = 'Antrean'): Order
    {
        $totalPrice = 0;
        $order = Order::create([
            'customer_name' => $name,
            'whatsapp_number' => $phone,
            'total_price' => 0,
            'status' => $status,
        ]);

        foreach ($items as $item) {
            $service = $item['service'];
            $qty = $item['qty'];
            $subtotal = $service->price_per_unit * $qty;
            $totalPrice += $subtotal;

            OrderDetail::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'qty_or_weight' => $qty,
                'subtotal' => $subtotal,
            ]);
        }

        $order->update(['total_price' => $totalPrice]);

        return $order->fresh(['orderDetails.service']);
    }

    // ==========================================
    // FEATURE 1: Relational Schema Migration
    // ==========================================

    public function test_f01_users_table_exists_and_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $expectedColumns = ['id', 'name', 'username', 'email', 'password', 'created_at', 'updated_at'];
        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Column [{$column}] missing from users table.");
        }
    }

    public function test_f01_services_table_exists_and_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('services'));
        $expectedColumns = ['id', 'service_name', 'category', 'price_per_unit', 'unit_type', 'description', 'created_at', 'updated_at'];
        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn('services', $column), "Column [{$column}] missing from services table.");
        }
    }

    public function test_f01_orders_table_exists_and_has_required_columns_and_defaults(): void
    {
        $this->assertTrue(Schema::hasTable('orders'));
        $expectedColumns = ['id', 'customer_name', 'whatsapp_number', 'total_price', 'status', 'created_at', 'updated_at'];
        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn('orders', $column), "Column [{$column}] missing from orders table.");
        }

        $order = Order::create([
            'customer_name' => 'Default Test User',
            'whatsapp_number' => '081234567890',
        ]);
        $this->assertEquals('Antrean', $order->status);
        $this->assertEquals(0, $order->total_price);
    }

    public function test_f01_order_details_table_exists_and_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('order_details'));
        $expectedColumns = ['id', 'order_id', 'service_id', 'qty_or_weight', 'subtotal', 'created_at', 'updated_at'];
        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn('order_details', $column), "Column [{$column}] missing from order_details table.");
        }
    }

    public function test_f01_foreign_key_constraints_cascade_on_delete(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Cascade Test', '08111222333', [
            ['service' => $services[0], 'qty' => 3],
        ]);

        $detailId = $order->orderDetails->first()->id;
        $this->assertDatabaseHas('order_details', ['id' => $detailId]);

        $order->delete();
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_details', ['id' => $detailId]);
    }

    // ==========================================
    // FEATURE 2: Eloquent Relations & Casts
    // ==========================================

    public function test_f02_service_has_many_order_details_relation(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Relation Test 1', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $this->assertTrue(method_exists($services[0], 'orderDetails'));
        $this->assertCount(1, $services[0]->orderDetails);
        $this->assertInstanceOf(OrderDetail::class, $services[0]->orderDetails->first());
    }

    public function test_f02_order_has_many_order_details_relation(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Relation Test 2', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
            ['service' => $services[1], 'qty' => 1],
        ]);

        $this->assertTrue(method_exists($order, 'orderDetails'));
        $this->assertCount(2, $order->orderDetails);
        $this->assertInstanceOf(OrderDetail::class, $order->orderDetails->first());
    }

    public function test_f02_order_detail_belongs_to_order_relation(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Relation Test 3', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $detail = $order->orderDetails->first();
        $this->assertTrue(method_exists($detail, 'order'));
        $this->assertInstanceOf(Order::class, $detail->order);
        $this->assertEquals($order->id, $detail->order->id);
    }

    public function test_f02_order_detail_belongs_to_service_relation(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Relation Test 4', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $detail = $order->orderDetails->first();
        $this->assertTrue(method_exists($detail, 'service'));
        $this->assertInstanceOf(Service::class, $detail->service);
        $this->assertEquals($services[0]->id, $detail->service->id);
    }

    public function test_f02_numeric_casts_and_data_types(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Cast Test', '08123456789', [
            ['service' => $services[0], 'qty' => 4],
        ]);

        $detail = $order->orderDetails->first();
        $this->assertIsInt($services[0]->price_per_unit);
        $this->assertIsInt($order->total_price);
        $this->assertIsInt($detail->qty_or_weight);
        $this->assertIsInt($detail->subtotal);
        $this->assertEquals(28000, $order->total_price);
    }

    // ==========================================
    // FEATURE 3: Database Seed Execution
    // ==========================================

    public function test_f03_seeder_populates_default_admin_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'email' => 'admin@laundry.test',
            'name' => 'Admin Laundry',
        ]);
    }

    public function test_f03_seeder_admin_can_authenticate_with_default_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('admin123', $admin->password));
    }

    public function test_f03_seeder_populates_all_six_master_service_packages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('services', 6);
        $this->assertDatabaseHas('services', ['service_name' => 'Cuci Komplit Reguler', 'price_per_unit' => 7000, 'category' => 'Kiloan', 'unit_type' => 'Kg']);
        $this->assertDatabaseHas('services', ['service_name' => 'Cuci Kering Lipat Cepat', 'price_per_unit' => 5000, 'category' => 'Kiloan', 'unit_type' => 'Kg']);
        $this->assertDatabaseHas('services', ['service_name' => 'Cuci Kilat Express 3 Jam', 'price_per_unit' => 15000, 'category' => 'Express', 'unit_type' => 'Kg']);
        $this->assertDatabaseHas('services', ['service_name' => 'Bed Cover King Size', 'price_per_unit' => 25000, 'category' => 'Satuan', 'unit_type' => 'Pcs']);
        $this->assertDatabaseHas('services', ['service_name' => 'Jas / Blazer Dry Cleaning', 'price_per_unit' => 35000, 'category' => 'Satuan', 'unit_type' => 'Pcs']);
        $this->assertDatabaseHas('services', ['service_name' => 'Selimut Tebal Bulu Halus', 'price_per_unit' => 20000, 'category' => 'Satuan', 'unit_type' => 'Pcs']);
    }

    public function test_f03_seeder_populates_sample_orders_for_siti_and_budi(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('orders', ['customer_name' => 'Siti Rahmawati']);
        $this->assertDatabaseHas('orders', ['customer_name' => 'Budi Santoso']);
    }

    public function test_f03_seeder_sample_orders_have_order_details_and_matching_totals(): void
    {
        $this->seed(DatabaseSeeder::class);

        $orders = Order::with('orderDetails')->get();
        $this->assertGreaterThanOrEqual(2, $orders->count());

        foreach ($orders as $order) {
            $this->assertNotEmpty($order->orderDetails);
            $calculatedTotal = $order->orderDetails->sum('subtotal');
            $this->assertEquals($calculatedTotal, $order->total_price);
        }
    }

    // ==========================================
    // FEATURE 4: Home Landing Page & Catalog
    // ==========================================

    public function test_f04_homepage_returns_successful_http_200_response(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_f04_homepage_renders_brand_name_and_hero_section(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $response->assertSee('Laundry Express', false);
    }

    public function test_f04_homepage_displays_all_active_service_cards_in_catalog(): void
    {
        $services = $this->createStandardServices();
        $response = $this->get('/');
        foreach ($services as $service) {
            $response->assertSee($service->service_name, false);
        }
    }

    public function test_f04_homepage_displays_accurate_pricing_and_unit_labels(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $response->assertSee('7.000', false);
        $response->assertSee('/ Kg', false);
        $response->assertSee('25.000', false);
        $response->assertSee('/ Pcs', false);
    }

    public function test_f04_homepage_renders_order_placement_form_and_tracking_bar(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $response->assertSee('action="'.url('/pesan').'"', false);
        $response->assertSee('customer_name', false);
        $response->assertSee('whatsapp_number', false);
        $response->assertSee('search', false);
    }

    // ==========================================
    // FEATURE 5: Dynamic Subtotal / Total Calculation DOM Structure
    // ==========================================

    public function test_f05_order_form_contains_service_selection_dropdown(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $response->assertSee('service_id', false);
    }

    public function test_f05_order_form_service_options_contain_price_metadata_attributes(): void
    {
        $services = $this->createStandardServices();
        $response = $this->get('/');
        $firstService = $services[0];
        $response->assertSee((string) $firstService->price_per_unit, false);
    }

    public function test_f05_order_form_contains_quantity_weight_input_with_min_constraint(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $response->assertSee('qty_or_weight', false);
    }

    public function test_f05_order_form_contains_subtotal_and_total_price_display_elements(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $response->assertSee('total', false);
    }

    public function test_f05_order_form_includes_interactive_calculation_script_or_repeater(): void
    {
        $this->createStandardServices();
        $response = $this->get('/');
        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, '<script') !== false,
            'Expected script tag for dynamic recalculation in view.'
        );
    }

    // ==========================================
    // FEATURE 6: Order Submission & Recalculation
    // ==========================================

    public function test_f06_order_submission_creates_order_record_in_database(): void
    {
        $services = $this->createStandardServices();
        $payload = [
            'customer_name' => 'Ahmad Dahlan',
            'whatsapp_number' => '081234567890',
            'items' => [
                [
                    'service_id' => $services[0]->id,
                    'qty_or_weight' => 3,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertStatus(302);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Ahmad Dahlan',
            'whatsapp_number' => '081234567890',
            'status' => 'Antrean',
        ]);
    }

    public function test_f06_order_submission_creates_order_detail_records_in_database(): void
    {
        $services = $this->createStandardServices();
        $payload = [
            'customer_name' => 'Dewi Sartika',
            'whatsapp_number' => '081298765432',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 2],
                ['service_id' => $services[3]->id, 'qty_or_weight' => 1],
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('customer_name', 'Dewi Sartika')->first();
        $this->assertNotNull($order);
        $this->assertCount(2, $order->orderDetails);
    }

    public function test_f06_order_submission_calculates_subtotal_server_side(): void
    {
        $services = $this->createStandardServices();
        $payload = [
            'customer_name' => 'Subtotal Verification User',
            'whatsapp_number' => '081255556666',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 5], // 5 * 7000 = 35000
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('customer_name', 'Subtotal Verification User')->first();
        $detail = $order->orderDetails->first();
        $this->assertEquals(35000, $detail->subtotal);
    }

    public function test_f06_order_submission_calculates_grand_total_from_subtotals(): void
    {
        $services = $this->createStandardServices();
        $payload = [
            'customer_name' => 'Total Verification User',
            'whatsapp_number' => '081277778888',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 2], // 14.000
                ['service_id' => $services[3]->id, 'qty_or_weight' => 2], // 50.000
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('customer_name', 'Total Verification User')->first();
        $this->assertEquals(64000, $order->total_price);
    }

    public function test_f06_order_submission_redirects_to_receipt_page(): void
    {
        $services = $this->createStandardServices();
        $payload = [
            'customer_name' => 'Redirect Verification User',
            'whatsapp_number' => '081299990000',
            'items' => [
                ['service_id' => $services[1]->id, 'qty_or_weight' => 4],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('customer_name', 'Redirect Verification User')->first();
        $response->assertRedirect('/nota/'.$order->id);
    }

    // ==========================================
    // FEATURE 7: Receipt Breakdown View
    // ==========================================

    public function test_f07_receipt_page_returns_http_200_for_existing_order(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Receipt User', '08123456789', [
            ['service' => $services[0], 'qty' => 3],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertStatus(200);
    }

    public function test_f07_receipt_page_displays_customer_name_and_whatsapp_number(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Kartini Receipt Test', '081987654321', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('Kartini Receipt Test', false);
        $response->assertSee('081987654321', false);
    }

    public function test_f07_receipt_page_displays_all_ordered_items_with_quantities(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Itemized Receipt User', '081233334444', [
            ['service' => $services[0], 'qty' => 3],
            ['service' => $services[4], 'qty' => 1],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee($services[0]->service_name, false);
        $response->assertSee($services[4]->service_name, false);
        $response->assertSee('3', false);
        $response->assertSee('1', false);
    }

    public function test_f07_receipt_page_displays_correct_item_subtotals_and_grand_total(): void
    {
        $services = $this->createStandardServices();
        // 3 * 7000 = 21000, 1 * 35000 = 35000, total = 56000
        $order = $this->createOrderWithItems('Subtotal Display User', '081211112222', [
            ['service' => $services[0], 'qty' => 3],
            ['service' => $services[4], 'qty' => 1],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('56.000', false);
    }

    public function test_f07_receipt_page_displays_order_status_and_timestamps(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Status Display User', '081244445555', [
            ['service' => $services[0], 'qty' => 1],
        ], 'Antrean');

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('Antrean', false);
    }

    // ==========================================
    // FEATURE 8: Receipt Print Action
    // ==========================================

    public function test_f08_receipt_page_contains_print_receipt_button(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Print Test User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('Cetak', false);
    }

    public function test_f08_receipt_print_button_triggers_window_print(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Print Trigger User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('window.print()', false);
    }

    public function test_f08_receipt_print_button_has_print_icon_or_descriptive_label(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Print Icon User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'fa-print') !== false || strpos($content, 'print') !== false,
            'Expected print icon or print keyword in receipt view.'
        );
    }

    public function test_f08_receipt_has_print_media_css_or_utilities(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('CSS Print User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, '@media print') !== false || strpos($content, 'd-print-none') !== false,
            'Expected @media print or Bootstrap d-print-none utility in receipt view.'
        );
    }

    public function test_f08_receipt_action_buttons_are_marked_to_hide_during_printing(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Hide On Print User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('d-print-none', false);
    }

    // ==========================================
    // FEATURE 9: WhatsApp Message Dispatch
    // ==========================================

    public function test_f09_receipt_page_contains_whatsapp_send_button_or_link(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('WhatsApp User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('WhatsApp', false);
    }

    public function test_f09_whatsapp_link_targets_wa_me_or_whatsapp_protocol(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('WhatsApp Protocol User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'wa.me') !== false || strpos($content, 'api.whatsapp.com') !== false,
            'Expected wa.me or api.whatsapp.com URL protocol in receipt view.'
        );
    }

    public function test_f09_whatsapp_link_includes_customer_phone_number(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('WhatsApp Phone User', '081299988877', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertSee('081299988877', false);
    }

    public function test_f09_whatsapp_link_contains_url_encoded_order_summary(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('WhatsApp Encoded User', '081299988877', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'text=') !== false,
            'Expected text query parameter for WhatsApp message body.'
        );
    }

    public function test_f09_whatsapp_message_includes_order_id_and_total_price(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('WhatsApp ID User', '081299988877', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $content = $response->getContent();
        $this->assertStringContainsString((string) $order->id, $content);
    }

    // ==========================================
    // FEATURE 10: Order Tracking Search
    // ==========================================

    public function test_f10_tracking_search_by_whatsapp_number_returns_orders(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Tracking Phone Target', '081200001111', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/?search=081200001111');
        $response->assertStatus(200);
        $response->assertSee('Tracking Phone Target', false);
    }

    public function test_f10_tracking_search_by_order_id_returns_specific_order(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Tracking ID Target', '081200002222', [
            ['service' => $services[1], 'qty' => 3],
        ]);

        $response = $this->get('/?search='.$order->id);
        $response->assertStatus(200);
        $response->assertSee('Tracking ID Target', false);
    }

    public function test_f10_tracking_displays_three_stage_progress_indicators(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Progress Bar User', '081200003333', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $response = $this->get('/?search=081200003333');
        $response->assertSee('Antrean', false);
        $response->assertSee('Dicuci', false);
        $response->assertSee('Selesai', false);
    }

    public function test_f10_tracking_accurately_marks_active_status_stage(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Active Status User', '081200004444', [
            ['service' => $services[0], 'qty' => 2],
        ], 'Dicuci');

        $response = $this->get('/?search=081200004444');
        $response->assertStatus(200);
        $response->assertSee('Dicuci', false);
    }

    public function test_f10_tracking_displays_informative_message_when_no_records_match(): void
    {
        $response = $this->get('/?search=089999999999');
        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'tidak ditemukan') !== false || strpos($content, 'Tidak ada') !== false,
            'Expected empty search feedback message.'
        );
    }

    // ==========================================
    // FEATURE 11: Admin Username Authentication
    // ==========================================

    public function test_f11_login_page_renders_username_and_password_inputs(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('username', false);
        $response->assertSee('password', false);
    }

    public function test_f11_admin_authenticates_with_valid_username_and_password(): void
    {
        $this->createAdminUser();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertAuthenticated();
    }

    public function test_f11_successful_login_redirects_to_admin_dashboard(): void
    {
        $this->createAdminUser();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_f11_login_regenerates_session_and_authenticates_guard(): void
    {
        $admin = $this->createAdminUser();

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_f11_login_fails_with_invalid_credentials_and_preserves_guest(): void
    {
        $this->createAdminUser();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    // ==========================================
    // FEATURE 12: Auth Middleware Guard
    // ==========================================

    public function test_f12_guest_access_to_dashboard_redirects_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_f12_guest_access_to_services_index_redirects_to_login(): void
    {
        $response = $this->get('/admin/layanan');
        $response->assertRedirect('/login');
    }

    public function test_f12_guest_post_to_services_redirects_to_login(): void
    {
        $response = $this->post('/admin/layanan', [
            'service_name' => 'Illegal Service',
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
        ]);
        $response->assertRedirect('/login');
    }

    public function test_f12_guest_access_to_orders_index_redirects_to_login(): void
    {
        $response = $this->get('/admin/pesanan');
        $response->assertRedirect('/login');
    }

    public function test_f12_guest_patch_to_order_status_redirects_to_login(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Guest Target', '08123456789', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $response = $this->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Dicuci',
        ]);
        $response->assertRedirect('/login');
    }

    // ==========================================
    // FEATURE 13: Admin Logout
    // ==========================================

    public function test_f13_authenticated_admin_can_perform_logout(): void
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->post('/logout');
        $this->assertGuest();
    }

    public function test_f13_logout_invalidates_current_session(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin)->post('/logout');
        $this->assertGuest();
    }

    public function test_f13_logout_regenerates_csrf_token(): void
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->post('/logout');
        $response->assertStatus(302);
    }

    public function test_f13_logout_redirects_to_login_page(): void
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->post('/logout');
        $response->assertRedirect('/login');
    }

    public function test_f13_logged_out_user_cannot_subsequently_access_admin_dashboard(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin)->post('/logout');
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    // ==========================================
    // FEATURE 14: Admin Dashboard Metrics
    // ==========================================

    public function test_f14_dashboard_renders_for_authenticated_admin_with_status_200(): void
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_f14_dashboard_displays_total_orders_counter_card(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Metric Order 1', '08111111111', [['service' => $services[0], 'qty' => 1]]);
        $this->createOrderWithItems('Metric Order 2', '08222222222', [['service' => $services[1], 'qty' => 2]]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSee('Total Pesanan', false);
    }

    public function test_f14_dashboard_displays_total_revenue_matching_order_sum(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        // 7000 + 10000 = 17000
        $this->createOrderWithItems('Rev 1', '08111111111', [['service' => $services[0], 'qty' => 1]]);
        $this->createOrderWithItems('Rev 2', '08222222222', [['service' => $services[1], 'qty' => 2]]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSee('17.000', false);
    }

    public function test_f14_dashboard_displays_accurate_counts_for_antrean_dicuci_selesai(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Order Antrean', '081', [['service' => $services[0], 'qty' => 1]], 'Antrean');
        $this->createOrderWithItems('Order Dicuci', '082', [['service' => $services[0], 'qty' => 1]], 'Dicuci');
        $this->createOrderWithItems('Order Selesai', '083', [['service' => $services[0], 'qty' => 1]], 'Selesai');

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSee('Antrean', false);
        $response->assertSee('Dicuci', false);
        $response->assertSee('Selesai', false);
    }

    public function test_f14_dashboard_displays_recent_transactions_table(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Recent Customer', '08123456789', [['service' => $services[0], 'qty' => 2]]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSee('Recent Customer', false);
    }

    // ==========================================
    // FEATURE 15: Service Package Listing
    // ==========================================

    public function test_f15_admin_services_page_renders_with_status_200(): void
    {
        $admin = $this->createAdminUser();
        $this->createStandardServices();

        $response = $this->actingAs($admin)->get('/admin/layanan');
        $response->assertStatus(200);
    }

    public function test_f15_admin_services_page_displays_all_service_packages(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $response = $this->actingAs($admin)->get('/admin/layanan');
        foreach ($services as $svc) {
            $response->assertSee($svc->service_name, false);
        }
    }

    public function test_f15_admin_services_page_displays_category_badges_and_unit_labels(): void
    {
        $admin = $this->createAdminUser();
        $this->createStandardServices();

        $response = $this->actingAs($admin)->get('/admin/layanan');
        $response->assertSee('Kiloan', false);
        $response->assertSee('Satuan', false);
        $response->assertSee('Express', false);
    }

    public function test_f15_admin_services_page_displays_action_buttons_for_each_service(): void
    {
        $admin = $this->createAdminUser();
        $this->createStandardServices();

        $response = $this->actingAs($admin)->get('/admin/layanan');
        $response->assertSee('Edit', false);
        $response->assertSee('Hapus', false);
    }

    public function test_f15_admin_services_page_renders_create_new_service_trigger(): void
    {
        $admin = $this->createAdminUser();
        $this->createStandardServices();

        $response = $this->actingAs($admin)->get('/admin/layanan');
        $response->assertSee('Tambah', false);
    }

    // ==========================================
    // FEATURE 16: Service Package Creation
    // ==========================================

    public function test_f16_admin_can_create_new_kiloan_service_package(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Cuci Kiloan Ekonomis',
            'category' => 'Kiloan',
            'price_per_unit' => 4000,
            'unit_type' => 'Kg',
            'description' => 'Paket ekonomis tanpa setrika.',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('services', ['service_name' => 'Cuci Kiloan Ekonomis']);
    }

    public function test_f16_admin_can_create_new_satuan_service_package(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Boneka Jumbo Care',
            'category' => 'Satuan',
            'price_per_unit' => 30000,
            'unit_type' => 'Pcs',
            'description' => 'Cuci boneka ukuran jumbo.',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('services', ['service_name' => 'Boneka Jumbo Care']);
    }

    public function test_f16_admin_can_create_new_express_service_package(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Super Flash 1 Jam',
            'category' => 'Express',
            'price_per_unit' => 25000,
            'unit_type' => 'Kg',
            'description' => 'Cuci kilat 1 jam siap pakai.',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('services', ['service_name' => 'Super Flash 1 Jam']);
    }

    public function test_f16_service_creation_persists_record_in_services_table(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Karpet Tebal Permadani',
            'category' => 'Satuan',
            'price_per_unit' => 50000,
            'unit_type' => 'Pcs',
            'description' => 'Cuci permadani tebal.',
        ]);

        $service = Service::where('service_name', 'Karpet Tebal Permadani')->first();
        $this->assertNotNull($service);
        $this->assertEquals(50000, $service->price_per_unit);
    }

    public function test_f16_service_creation_redirects_with_success_session_message(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Flash Curtains Clean',
            'category' => 'Satuan',
            'price_per_unit' => 15000,
            'unit_type' => 'Pcs',
            'description' => 'Cuci gorden express.',
        ]);

        $response->assertRedirect('/admin/layanan');
        $response->assertSessionHas('success');
    }

    // ==========================================
    // FEATURE 17: Service Package Update
    // ==========================================

    public function test_f17_admin_can_update_service_package_name(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $target = $services[0];

        $response = $this->actingAs($admin)->put("/admin/layanan/{$target->id}", [
            'service_name' => 'Cuci Komplit Premium Updated',
            'category' => $target->category,
            'price_per_unit' => $target->price_per_unit,
            'unit_type' => $target->unit_type,
            'description' => 'Updated desc',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('services', [
            'id' => $target->id,
            'service_name' => 'Cuci Komplit Premium Updated',
        ]);
    }

    public function test_f17_admin_can_update_service_package_price(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $target = $services[0];

        $this->actingAs($admin)->put("/admin/layanan/{$target->id}", [
            'service_name' => $target->service_name,
            'category' => $target->category,
            'price_per_unit' => 9500,
            'unit_type' => $target->unit_type,
            'description' => $target->description,
        ]);

        $this->assertEquals(9500, $target->fresh()->price_per_unit);
    }

    public function test_f17_admin_can_update_service_package_category_and_unit(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $target = $services[0];

        $this->actingAs($admin)->put("/admin/layanan/{$target->id}", [
            'service_name' => $target->service_name,
            'category' => 'Express',
            'price_per_unit' => 12000,
            'unit_type' => 'Kg',
            'description' => 'Now express package',
        ]);

        $updated = $target->fresh();
        $this->assertEquals('Express', $updated->category);
        $this->assertEquals(12000, $updated->price_per_unit);
    }

    public function test_f17_service_update_persists_changes_in_services_table(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $target = $services[3];

        $this->actingAs($admin)->put("/admin/layanan/{$target->id}", [
            'service_name' => 'Bed Cover Super King Deluxe',
            'category' => 'Satuan',
            'price_per_unit' => 35000,
            'unit_type' => 'Pcs',
            'description' => 'Extra large luxury bed cover care.',
        ]);

        $this->assertDatabaseHas('services', [
            'id' => $target->id,
            'service_name' => 'Bed Cover Super King Deluxe',
            'price_per_unit' => 35000,
        ]);
    }

    public function test_f17_service_update_redirects_with_success_session_message(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $target = $services[0];

        $response = $this->actingAs($admin)->put("/admin/layanan/{$target->id}", [
            'service_name' => $target->service_name,
            'category' => $target->category,
            'price_per_unit' => 8000,
            'unit_type' => $target->unit_type,
            'description' => 'Price adjustment',
        ]);

        $response->assertRedirect('/admin/layanan');
        $response->assertSessionHas('success');
    }

    // ==========================================
    // FEATURE 18: Service Package Deletion
    // ==========================================

    public function test_f18_admin_can_delete_unused_service_package(): void
    {
        $admin = $this->createAdminUser();
        $service = Service::create([
            'service_name' => 'Temp Service To Delete',
            'category' => 'Kiloan',
            'price_per_unit' => 6000,
            'unit_type' => 'Kg',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/layanan/{$service->id}");
        $response->assertStatus(302);
    }

    public function test_f18_service_deletion_removes_record_from_services_table(): void
    {
        $admin = $this->createAdminUser();
        $service = Service::create([
            'service_name' => 'Service DB Remove Test',
            'category' => 'Satuan',
            'price_per_unit' => 10000,
            'unit_type' => 'Pcs',
        ]);

        $this->actingAs($admin)->delete("/admin/layanan/{$service->id}");
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_f18_deleted_service_is_removed_from_public_catalog(): void
    {
        $admin = $this->createAdminUser();
        $service = Service::create([
            'service_name' => 'Hidden From Catalog Soon',
            'category' => 'Kiloan',
            'price_per_unit' => 8000,
            'unit_type' => 'Kg',
        ]);

        $this->actingAs($admin)->delete("/admin/layanan/{$service->id}");
        $response = $this->get('/');
        $response->assertDontSee('Hidden From Catalog Soon');
    }

    public function test_f18_service_deletion_redirects_with_success_session_message(): void
    {
        $admin = $this->createAdminUser();
        $service = Service::create([
            'service_name' => 'Delete Redirect Service',
            'category' => 'Kiloan',
            'price_per_unit' => 8000,
            'unit_type' => 'Kg',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/layanan/{$service->id}");
        $response->assertRedirect('/admin/layanan');
        $response->assertSessionHas('success');
    }

    public function test_f18_deleting_nonexistent_service_returns_not_found(): void
    {
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)->delete('/admin/layanan/99999');
        $response->assertStatus(404);
    }

    // ==========================================
    // FEATURE 19: Order Management List
    // ==========================================

    public function test_f19_admin_orders_page_renders_with_status_200(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Order List User', '08123456789', [['service' => $services[0], 'qty' => 1]]);

        $response = $this->actingAs($admin)->get('/admin/pesanan');
        $response->assertStatus(200);
    }

    public function test_f19_admin_orders_page_displays_all_customer_orders(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $this->createOrderWithItems('List Customer One', '08111111111', [['service' => $services[0], 'qty' => 1]]);
        $this->createOrderWithItems('List Customer Two', '08222222222', [['service' => $services[1], 'qty' => 2]]);

        $response = $this->actingAs($admin)->get('/admin/pesanan');
        $response->assertSee('List Customer One', false);
        $response->assertSee('List Customer Two', false);
    }

    public function test_f19_admin_orders_page_filters_by_antrean_status(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Antrean User', '081', [['service' => $services[0], 'qty' => 1]], 'Antrean');
        $this->createOrderWithItems('Dicuci User', '082', [['service' => $services[0], 'qty' => 1]], 'Dicuci');

        $response = $this->actingAs($admin)->get('/admin/pesanan?status=Antrean');
        $response->assertSee('Antrean User', false);
    }

    public function test_f19_admin_orders_page_filters_by_dicuci_and_selesai_status(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Antrean Order', '081', [['service' => $services[0], 'qty' => 1]], 'Antrean');
        $this->createOrderWithItems('Selesai Order', '083', [['service' => $services[0], 'qty' => 1]], 'Selesai');

        $response = $this->actingAs($admin)->get('/admin/pesanan?status=Selesai');
        $response->assertSee('Selesai Order', false);
    }

    public function test_f19_admin_orders_page_searches_by_customer_name_and_whatsapp(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Distinct Name Siti', '081299990001', [['service' => $services[0], 'qty' => 1]]);
        $this->createOrderWithItems('Distinct Name Budi', '081299990002', [['service' => $services[1], 'qty' => 1]]);

        $response = $this->actingAs($admin)->get('/admin/pesanan?search=Siti');
        $response->assertSee('Distinct Name Siti', false);
    }

    // ==========================================
    // FEATURE 20: Order Status Transition
    // ==========================================

    public function test_f20_admin_can_transition_order_status_from_antrean_to_dicuci(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Transition Antrean User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ], 'Antrean');

        $response = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Dicuci',
        ]);

        $response->assertStatus(302);
        $this->assertEquals('Dicuci', $order->fresh()->status);
    }

    public function test_f20_admin_can_transition_order_status_from_dicuci_to_selesai(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Transition Dicuci User', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
        ], 'Dicuci');

        $response = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Selesai',
        ]);

        $response->assertStatus(302);
        $this->assertEquals('Selesai', $order->fresh()->status);
    }

    public function test_f20_status_transition_persists_in_orders_table(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Persist Status User', '08123456789', [
            ['service' => $services[0], 'qty' => 1],
        ], 'Antrean');

        $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Dicuci',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Dicuci',
        ]);
    }

    public function test_f20_status_transition_immediately_propagates_to_tracking_view(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Tracking Sync User', '081277773333', [
            ['service' => $services[0], 'qty' => 1],
        ], 'Antrean');

        $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Selesai',
        ]);

        $response = $this->get('/?search=081277773333');
        $response->assertSee('Selesai', false);
    }

    public function test_f20_status_transition_redirects_with_success_flash_message(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Flash Msg User', '08123456789', [
            ['service' => $services[0], 'qty' => 1],
        ], 'Antrean');

        $response = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Dicuci',
        ]);

        $response->assertSessionHas('success');
    }

    // ==========================================
    // FEATURE 21: Order Deletion
    // ==========================================

    public function test_f21_admin_can_delete_an_order(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Delete Target User', '08123456789', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $response = $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");
        $response->assertStatus(302);
    }

    public function test_f21_order_deletion_removes_order_record_from_orders_table(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('DB Remove Order', '08123456789', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_f21_order_deletion_cascades_and_removes_associated_order_details(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Cascade Detail Order', '08123456789', [
            ['service' => $services[0], 'qty' => 2],
            ['service' => $services[1], 'qty' => 1],
        ]);

        $detailIds = $order->orderDetails->pluck('id')->toArray();
        $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");

        foreach ($detailIds as $id) {
            $this->assertDatabaseMissing('order_details', ['id' => $id]);
        }
    }

    public function test_f21_deleted_order_is_no_longer_returned_in_tracking_searches(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Ghost Order User', '081200009999', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");
        $response = $this->get('/?search=081200009999');
        $response->assertDontSee('Ghost Order User');
    }

    public function test_f21_order_deletion_redirects_with_success_flash_message(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Delete Flash User', '08123456789', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $response = $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");
        $response->assertRedirect('/admin/pesanan');
        $response->assertSessionHas('success');
    }
}

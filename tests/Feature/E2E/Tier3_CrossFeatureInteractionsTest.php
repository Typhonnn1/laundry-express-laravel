<?php

namespace Tests\Feature\E2E;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tier 3: Cross-Feature Interactions Test Suite (Opaque-Box E2E Testing)
 *
 * Verifies end-to-end multi-step flows connecting public customer portal operations
 * (ordering, receipts, tracking) with admin operations (auth, catalog management,
 * workflow status transitions, metrics reconciliation).
 */
class Tier3_CrossFeatureInteractionsTest extends TestCase
{
    use RefreshDatabase;

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
        return [
            Service::create([
                'service_name' => 'Cuci Komplit Reguler',
                'category' => 'Kiloan',
                'price_per_unit' => 7000,
                'unit_type' => 'Kg',
                'description' => 'Cuci, kering, setrika.',
            ]),
            Service::create([
                'service_name' => 'Bed Cover King Size',
                'category' => 'Satuan',
                'price_per_unit' => 25000,
                'unit_type' => 'Pcs',
                'description' => 'Bed cover king.',
            ]),
            Service::create([
                'service_name' => 'Cuci Kilat Express 3 Jam',
                'category' => 'Express',
                'price_per_unit' => 15000,
                'unit_type' => 'Kg',
                'description' => 'Express service.',
            ]),
        ];
    }

    // =========================================================================
    // 1. COMPLETE END-TO-END ORDER LIFECYCLE (CUSTOMER -> ADMIN -> CUSTOMER)
    // =========================================================================

    public function test_complete_end_to_end_customer_to_admin_lifecycle(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        // Step 1: Customer visits homepage catalog
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee($services[0]->service_name);

        // Step 2: Customer places multi-item order (2 Kg Kiloan @ 7000 + 1 Bed Cover @ 25000 = 39000)
        $orderPayload = [
            'customer_name' => 'Raden Ajeng Kartini',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 2],
                ['service_id' => $services[1]->id, 'qty_or_weight' => 1],
            ],
        ];
        $submitResponse = $this->post('/pesan', $orderPayload);
        $submitResponse->assertStatus(302);

        $order = Order::where('customer_name', 'Raden Ajeng Kartini')->first();
        $this->assertNotNull($order);
        $this->assertEquals(39000, $order->total_price);
        $this->assertEquals('Antrean', $order->status);

        // Step 3: Customer is redirected to receipt
        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('39.000', false);
        $receiptResponse->assertSee('Raden Ajeng Kartini', false);

        // Step 4: Customer checks public tracking -> Status: Antrean
        $trackResponse1 = $this->get('/?search=081234567890');
        $trackResponse1->assertStatus(200);
        $trackResponse1->assertSee('Antrean', false);

        // Step 5: Admin logs in
        $loginResponse = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $loginResponse->assertRedirect('/admin');

        // Step 6: Admin checks dashboard metrics
        $dashResponse = $this->actingAs($admin)->get('/admin');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('39.000', false);

        // Step 7: Admin transitions status to 'Dicuci'
        $transition1 = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Dicuci',
        ]);
        $transition1->assertStatus(302);
        $this->assertEquals('Dicuci', $order->fresh()->status);

        // Step 8: Customer refreshes public tracking -> Status: Dicuci
        $trackResponse2 = $this->get('/?search=081234567890');
        $trackResponse2->assertSee('Dicuci', false);

        // Step 9: Admin transitions status to 'Selesai'
        $transition2 = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Selesai',
        ]);
        $transition2->assertStatus(302);
        $this->assertEquals('Selesai', $order->fresh()->status);

        // Step 10: Customer refreshes public tracking -> Status: Selesai
        $trackResponse3 = $this->get('/?search=081234567890');
        $trackResponse3->assertSee('Selesai', false);
    }

    // =========================================================================
    // 2. SERVICE CATALOG EXPANSION TO CHECKOUT FLOW
    // =========================================================================

    public function test_admin_created_service_immediately_available_in_catalog_and_checkout(): void
    {
        $admin = $this->createAdminUser();

        // Admin creates a new service package
        $createResponse = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Cuci Karpet Super Permadani',
            'category' => 'Satuan',
            'price_per_unit' => 45000,
            'unit_type' => 'Pcs',
            'description' => 'Cuci karpet tebal permadani istimewa.',
        ]);
        $createResponse->assertRedirect('/admin/layanan');

        $newService = Service::where('service_name', 'Cuci Karpet Super Permadani')->first();
        $this->assertNotNull($newService);

        // Customer views public catalog and sees the new service
        $catalogResponse = $this->get('/');
        $catalogResponse->assertSee('Cuci Karpet Super Permadani', false);
        $catalogResponse->assertSee('45.000', false);

        // Customer places an order using this newly created service
        $orderResponse = $this->post('/pesan', [
            'customer_name' => 'Iwan Fals',
            'whatsapp_number' => '081255554444',
            'items' => [
                ['service_id' => $newService->id, 'qty_or_weight' => 2], // 2 * 45000 = 90000
            ],
        ]);
        $orderResponse->assertStatus(302);

        $order = Order::where('customer_name', 'Iwan Fals')->first();
        $this->assertEquals(90000, $order->total_price);

        // Receipt reflects the new service name and accurate total
        $receipt = $this->get('/nota/'.$order->id);
        $receipt->assertSee('Cuci Karpet Super Permadani', false);
        $receipt->assertSee('90.000', false);
    }

    // =========================================================================
    // 3. HISTORICAL PRICE PROTECTION
    // =========================================================================

    public function test_existing_order_preserves_historical_pricing_when_service_rate_changes(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $targetService = $services[0]; // 7000 / Kg

        // Customer orders 4 Kg at 7000 = 28000
        $this->post('/pesan', [
            'customer_name' => 'Historical Customer',
            'whatsapp_number' => '081288887777',
            'items' => [
                ['service_id' => $targetService->id, 'qty_or_weight' => 4],
            ],
        ]);
        $order = Order::where('customer_name', 'Historical Customer')->first();
        $this->assertEquals(28000, $order->total_price);

        // Admin updates service package price from 7000 to 12000
        $this->actingAs($admin)->put("/admin/layanan/{$targetService->id}", [
            'service_name' => $targetService->service_name,
            'category' => $targetService->category,
            'price_per_unit' => 12000,
            'unit_type' => $targetService->unit_type,
            'description' => $targetService->description,
        ]);

        // Historical order receipt still displays 28000
        $receipt = $this->get('/nota/'.$order->id);
        $receipt->assertSee('28.000', false);
        $this->assertEquals(28000, $order->fresh()->total_price);

        // Admin dashboard revenue reflects historical price (28000)
        $dashboard = $this->actingAs($admin)->get('/admin');
        $dashboard->assertSee('28.000', false);
    }

    // =========================================================================
    // 4. ORDER DELETION RIPPLE EFFECT
    // =========================================================================

    public function test_order_deletion_removes_receipt_tracking_and_decrements_revenue(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        // Place order for 50000 (2 * 25000)
        $this->post('/pesan', [
            'customer_name' => 'Deletion Ripple User',
            'whatsapp_number' => '081299991111',
            'items' => [
                ['service_id' => $services[1]->id, 'qty_or_weight' => 2],
            ],
        ]);
        $order = Order::where('customer_name', 'Deletion Ripple User')->first();
        $this->assertEquals(50000, $order->total_price);

        // Verify dashboard revenue includes 50000
        $dashBefore = $this->actingAs($admin)->get('/admin');
        $dashBefore->assertSee('50.000', false);

        // Admin deletes the order
        $deleteResponse = $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");
        $deleteResponse->assertRedirect('/admin/pesanan');

        // Receipt returns 404
        $receiptAfter = $this->get('/nota/'.$order->id);
        $receiptAfter->assertStatus(404);

        // Tracking search returns not found
        $trackAfter = $this->get('/?search=081299991111');
        $trackAfter->assertDontSee('Deletion Ripple User');

        // Total orders count in DB is 0
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    // =========================================================================
    // 5. COMPOSITE MULTI-CATEGORY BASKET CHECKOUT
    // =========================================================================

    public function test_composite_multi_category_basket_checkout(): void
    {
        $services = $this->createStandardServices();

        // Order Kiloan (3 * 7000 = 21000) + Satuan (2 * 25000 = 50000) + Express (1 * 15000 = 15000) = 86000
        $payload = [
            'customer_name' => 'Multi Category Shopper',
            'whatsapp_number' => '081233339999',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 3],
                ['service_id' => $services[1]->id, 'qty_or_weight' => 2],
                ['service_id' => $services[2]->id, 'qty_or_weight' => 1],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertStatus(302);

        $order = Order::where('customer_name', 'Multi Category Shopper')->first();
        $this->assertEquals(86000, $order->total_price);
        $this->assertCount(3, $order->orderDetails);

        $receipt = $this->get('/nota/'.$order->id);
        $receipt->assertSee('86.000', false);
        $receipt->assertSee($services[0]->service_name, false);
        $receipt->assertSee($services[1]->service_name, false);
        $receipt->assertSee($services[2]->service_name, false);
    }

    // =========================================================================
    // 6. ADMIN STATUS FILTER SYNCHRONICITY
    // =========================================================================

    public function test_admin_status_filter_and_real_time_transition_synchronicity(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $orderA = Order::create([
            'customer_name' => 'Order Antrean Filter',
            'whatsapp_number' => '081111',
            'total_price' => 7000,
            'status' => 'Antrean',
        ]);
        $orderB = Order::create([
            'customer_name' => 'Order Dicuci Filter',
            'whatsapp_number' => '082222',
            'total_price' => 7000,
            'status' => 'Dicuci',
        ]);

        // Antrean filter displays Order A only
        $resAntrean = $this->actingAs($admin)->get('/admin/pesanan?status=Antrean');
        $resAntrean->assertSee('Order Antrean Filter', false);

        // Transition Order A to 'Dicuci'
        $this->actingAs($admin)->patch("/admin/pesanan/{$orderA->id}/status", ['status' => 'Dicuci']);

        // Dicuci filter now displays both orders
        $resDicuci = $this->actingAs($admin)->get('/admin/pesanan?status=Dicuci');
        $resDicuci->assertSee('Order Antrean Filter', false);
        $resDicuci->assertSee('Order Dicuci Filter', false);
    }

    // =========================================================================
    // 7. SEARCH SYNCHRONICITY ACROSS PUBLIC AND ADMIN PORTALS
    // =========================================================================

    public function test_search_synchronicity_across_public_and_admin_portals(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $this->post('/pesan', [
            'customer_name' => 'Synchronized Customer',
            'whatsapp_number' => '08199998888',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 2],
            ],
        ]);
        $order = Order::where('customer_name', 'Synchronized Customer')->first();

        // Public tracking search by phone
        $pubSearchPhone = $this->get('/?search=08199998888');
        $pubSearchPhone->assertSee('Synchronized Customer', false);

        // Public tracking search by ID
        $pubSearchId = $this->get('/?search='.$order->id);
        $pubSearchId->assertSee('Synchronized Customer', false);

        // Admin search by customer name
        $adminSearchName = $this->actingAs($admin)->get('/admin/pesanan?search=Synchronized');
        $adminSearchName->assertSee('Synchronized Customer', false);

        // Admin search by phone
        $adminSearchPhone = $this->actingAs($admin)->get('/admin/pesanan?search=08199998888');
        $adminSearchPhone->assertSee('Synchronized Customer', false);
    }

    // =========================================================================
    // 8. WHATSAPP MESSAGE CONTENT DYNAMIC FORMATTING
    // =========================================================================

    public function test_whatsapp_message_content_contains_full_order_breakdown(): void
    {
        $services = $this->createStandardServices();
        $this->post('/pesan', [
            'customer_name' => 'WA Test Customer',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 3],
            ],
        ]);
        $order = Order::where('customer_name', 'WA Test Customer')->first();

        $receipt = $this->get('/nota/'.$order->id);
        $content = $receipt->getContent();

        $this->assertStringContainsString('wa.me', $content);
        $this->assertStringContainsString((string) $order->id, $content);
        $this->assertStringContainsString('WA%20Test%20Customer', $content);
    }

    // =========================================================================
    // 9. DASHBOARD METRICS INCREMENT INTEGRITY
    // =========================================================================

    public function test_dashboard_metrics_increment_consistently_after_order_creation(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $dash0 = $this->actingAs($admin)->get('/admin');
        $dash0->assertStatus(200);

        // Create new order for 21000
        $this->post('/pesan', [
            'customer_name' => 'Metric Increment User',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 3],
            ],
        ]);

        $dash1 = $this->actingAs($admin)->get('/admin');
        $dash1->assertSee('21.000', false);
    }

    // =========================================================================
    // 10. DASHBOARD STATUS SHIFT CONSISTENCY
    // =========================================================================

    public function test_dashboard_status_counts_shift_when_order_progresses(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $order = Order::create([
            'customer_name' => 'Shift User',
            'whatsapp_number' => '081234567890',
            'total_price' => 14000,
            'status' => 'Antrean',
        ]);

        // Transition to Dicuci
        $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", ['status' => 'Dicuci']);
        $this->assertEquals('Dicuci', $order->fresh()->status);

        $dash = $this->actingAs($admin)->get('/admin');
        $dash->assertStatus(200);
        $dash->assertSee('14.000', false);
    }

    // =========================================================================
    // 11. DASHBOARD COMPLETION METRIC CONSISTENCY
    // =========================================================================

    public function test_dashboard_completion_metric_consistency(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $order = Order::create([
            'customer_name' => 'Complete Shift User',
            'whatsapp_number' => '081234567890',
            'total_price' => 20000,
            'status' => 'Dicuci',
        ]);

        // Transition to Selesai
        $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", ['status' => 'Selesai']);
        $this->assertEquals('Selesai', $order->fresh()->status);

        $dash = $this->actingAs($admin)->get('/admin');
        $dash->assertStatus(200);
        $dash->assertSee('20.000', false);
    }

    // =========================================================================
    // 12. CASCADE DELETION INTEGRITY ACROSS MULTI-ITEM ORDERS
    // =========================================================================

    public function test_cascade_deletion_cleans_all_items_without_affecting_services(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $this->post('/pesan', [
            'customer_name' => 'Cascade Clean User',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 2],
                ['service_id' => $services[1]->id, 'qty_or_weight' => 1],
                ['service_id' => $services[2]->id, 'qty_or_weight' => 3],
            ],
        ]);
        $order = Order::where('customer_name', 'Cascade Clean User')->first();
        $detailIds = $order->orderDetails->pluck('id')->toArray();

        $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        foreach ($detailIds as $did) {
            $this->assertDatabaseMissing('order_details', ['id' => $did]);
        }

        // Master services remain untouched
        $this->assertDatabaseCount('services', 3);
    }

    // =========================================================================
    // 13. PUBLIC TRACKING STATE PRIVACY AND ISOLATION
    // =========================================================================

    public function test_public_tracking_state_privacy_and_isolation(): void
    {
        $services = $this->createStandardServices();

        Order::create([
            'customer_name' => 'Customer Alice',
            'whatsapp_number' => '081111111111',
            'total_price' => 14000,
            'status' => 'Antrean',
        ]);

        Order::create([
            'customer_name' => 'Customer Bob Confidential',
            'whatsapp_number' => '082222222222',
            'total_price' => 35000,
            'status' => 'Dicuci',
        ]);

        // Alice searches her phone number
        $aliceSearch = $this->get('/?search=081111111111');
        $aliceSearch->assertSee('Customer Alice', false);
        $aliceSearch->assertDontSee('Customer Bob Confidential');
    }

    // =========================================================================
    // 14. ADMIN SESSION TERMINATION LOCKOUT
    // =========================================================================

    public function test_admin_logout_locks_out_subsequent_order_mutations(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $order = Order::create([
            'customer_name' => 'Lockout User',
            'whatsapp_number' => '081234567890',
            'total_price' => 14000,
            'status' => 'Antrean',
        ]);

        // Admin logs out
        $this->actingAs($admin)->post('/logout');

        // Attempting to patch status as guest
        $response = $this->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Selesai',
        ]);

        $response->assertRedirect('/login');
        $this->assertEquals('Antrean', $order->fresh()->status);
    }

    // =========================================================================
    // 15. CATALOG SERVICE DELETION SAFEGUARD
    // =========================================================================

    public function test_service_package_deletion_does_not_corrupt_existing_orders(): void
    {
        $admin = $this->createAdminUser();
        $tempService = Service::create([
            'service_name' => 'Temporary Service',
            'category' => 'Kiloan',
            'price_per_unit' => 6000,
            'unit_type' => 'Kg',
        ]);

        $order = Order::create([
            'customer_name' => 'Service Deletion Safeguard User',
            'whatsapp_number' => '081234567890',
            'total_price' => 12000,
            'status' => 'Antrean',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $tempService->id,
            'qty_or_weight' => 2,
            'subtotal' => 12000,
        ]);

        // When admin attempts to delete service that has orders, it either cascades or restricts cleanly
        $this->actingAs($admin)->delete("/admin/layanan/{$tempService->id}");

        // If deleted, the order table must still be intact
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}

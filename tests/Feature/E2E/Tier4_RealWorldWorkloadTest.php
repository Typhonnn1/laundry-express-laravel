<?php

namespace Tests\Feature\E2E;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tier 4: Real-World Application Workload Scenarios Test Suite (Opaque-Box E2E Testing)
 *
 * Simulates full commercial operations: multi-customer high-volume daily pipelines,
 * bulk workflow progressions, mixed-unit calculations, catalog expansions, and financial audit reconciliation.
 */
class Tier4_RealWorldWorkloadTest extends TestCase
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

    protected function createStandardMasterCatalog(): array
    {
        return [
            Service::create([
                'service_name' => 'Cuci Komplit Reguler',
                'category' => 'Kiloan',
                'price_per_unit' => 7000,
                'unit_type' => 'Kg',
                'description' => 'Cuci, kering, dan setrika rapi.',
            ]),
            Service::create([
                'service_name' => 'Cuci Kering Lipat Cepat',
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
                'description' => 'Cuci bersih dan lipat rapi.',
            ]),
            Service::create([
                'service_name' => 'Cuci Kilat Express 3 Jam',
                'category' => 'Express',
                'price_per_unit' => 15000,
                'unit_type' => 'Kg',
                'description' => 'Layanan express 3 jam selesai.',
            ]),
            Service::create([
                'service_name' => 'Bed Cover King Size',
                'category' => 'Satuan',
                'price_per_unit' => 25000,
                'unit_type' => 'Pcs',
                'description' => 'Pencucian khusus bed cover besar.',
            ]),
            Service::create([
                'service_name' => 'Jas / Blazer Dry Cleaning',
                'category' => 'Satuan',
                'price_per_unit' => 35000,
                'unit_type' => 'Pcs',
                'description' => 'Dry cleaning profesional untuk jas/blazer.',
            ]),
            Service::create([
                'service_name' => 'Selimut Tebal Bulu Halus',
                'category' => 'Satuan',
                'price_per_unit' => 20000,
                'unit_type' => 'Pcs',
                'description' => 'Pencucian higienis selimut tebal.',
            ]),
        ];
    }

    // =========================================================================
    // SCENARIO 1: FULL BUSINESS DAY SIMULATION (10 CUSTOMERS)
    // =========================================================================

    public function test_full_business_day_ten_customer_order_pipeline(): void
    {
        $services = $this->createStandardMasterCatalog();

        $dailyCustomers = [
            ['name' => 'Budi Santoso', 'phone' => '081211110001', 'items' => [['id' => $services[0]->id, 'qty' => 5]]], // 35.000
            ['name' => 'Siti Rahmawati', 'phone' => '081211110002', 'items' => [['id' => $services[1]->id, 'qty' => 4], ['id' => $services[3]->id, 'qty' => 1]]], // 20.000 + 25.000 = 45.000
            ['name' => 'Agus Prasetyo', 'phone' => '081211110003', 'items' => [['id' => $services[2]->id, 'qty' => 3]]], // 45.000
            ['name' => 'Dewi Lestari', 'phone' => '081211110004', 'items' => [['id' => $services[4]->id, 'qty' => 2]]], // 70.000
            ['name' => 'Hendra Wijaya', 'phone' => '081211110005', 'items' => [['id' => $services[5]->id, 'qty' => 2], ['id' => $services[0]->id, 'qty' => 2]]], // 40.000 + 14.000 = 54.000
            ['name' => 'Rina Andriani', 'phone' => '081211110006', 'items' => [['id' => $services[0]->id, 'qty' => 10]]], // 70.000
            ['name' => 'Eko Kurniawan', 'phone' => '081211110007', 'items' => [['id' => $services[2]->id, 'qty' => 5]]], // 75.000
            ['name' => 'Maya Indah', 'phone' => '081211110008', 'items' => [['id' => $services[3]->id, 'qty' => 2], ['id' => $services[5]->id, 'qty' => 1]]], // 50.000 + 20.000 = 70.000
            ['name' => 'Fajar Nugraha', 'phone' => '081211110009', 'items' => [['id' => $services[1]->id, 'qty' => 6]]], // 30.000
            ['name' => 'Tri Wahyuni', 'phone' => '081211110010', 'items' => [['id' => $services[4]->id, 'qty' => 1], ['id' => $services[0]->id, 'qty' => 3]]], // 35.000 + 21.000 = 56.000
        ];

        foreach ($dailyCustomers as $cust) {
            $formattedItems = [];
            foreach ($cust['items'] as $item) {
                $formattedItems[] = ['service_id' => $item['id'], 'qty_or_weight' => $item['qty']];
            }

            $response = $this->post('/pesan', [
                'customer_name' => $cust['name'],
                'whatsapp_number' => $cust['phone'],
                'items' => $formattedItems,
            ]);

            $response->assertStatus(302);
            $this->assertDatabaseHas('orders', ['customer_name' => $cust['name']]);
        }

        $this->assertDatabaseCount('orders', 10);
        $totalRevenue = Order::sum('total_price');
        $this->assertEquals(550000, $totalRevenue);
    }

    // =========================================================================
    // SCENARIO 2: FINANCIAL REVENUE RECONCILIATION
    // =========================================================================

    public function test_financial_revenue_reconciliation_under_realistic_volume(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardMasterCatalog();

        $expectedGrandTotal = 0;
        for ($i = 1; $i <= 15; $i++) {
            $qty = ($i % 5) + 1;
            $service = $services[$i % 6];
            $subtotal = $service->price_per_unit * $qty;
            $expectedGrandTotal += $subtotal;

            $this->post('/pesan', [
                'customer_name' => "Audit Customer {$i}",
                'whatsapp_number' => '08129999'.sprintf('%04d', $i),
                'items' => [
                    ['service_id' => $service->id, 'qty_or_weight' => $qty],
                ],
            ]);
        }

        $actualDbSum = Order::sum('total_price');
        $this->assertEquals($expectedGrandTotal, $actualDbSum);

        // Verify Admin Dashboard displays this exact total
        $dashboardResponse = $this->actingAs($admin)->get('/admin');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee(number_format($expectedGrandTotal, 0, ',', '.'), false);
    }

    // =========================================================================
    // SCENARIO 3: BULK PIPELINE PROGRESSION ACROSS MULTIPLE ORDERS
    // =========================================================================

    public function test_admin_bulk_pipeline_progression_across_all_orders(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardMasterCatalog();

        $orderIds = [];
        for ($i = 1; $i <= 12; $i++) {
            $order = Order::create([
                'customer_name' => "Pipeline Customer {$i}",
                'whatsapp_number' => '08130000'.sprintf('%04d', $i),
                'total_price' => 20000,
                'status' => 'Antrean',
            ]);
            $orderIds[] = $order->id;
        }

        // Move 8 orders to 'Dicuci'
        for ($i = 0; $i < 8; $i++) {
            $this->actingAs($admin)->patch("/admin/pesanan/{$orderIds[$i]}/status", ['status' => 'Dicuci']);
        }

        // Move 5 of those to 'Selesai'
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin)->patch("/admin/pesanan/{$orderIds[$i]}/status", ['status' => 'Selesai']);
        }

        // Counts: 4 in Antrean, 3 in Dicuci, 5 in Selesai (Total 12)
        $this->assertEquals(4, Order::where('status', 'Antrean')->count());
        $this->assertEquals(3, Order::where('status', 'Dicuci')->count());
        $this->assertEquals(5, Order::where('status', 'Selesai')->count());

        $dashboard = $this->actingAs($admin)->get('/admin');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('Total Pesanan', false);
    }

    // =========================================================================
    // SCENARIO 4: MIXED UNIT TYPE HIGH VALUE BASKET
    // =========================================================================

    public function test_mixed_unit_type_high_volume_basket_calculations(): void
    {
        $services = $this->createStandardMasterCatalog();

        // 50 Kg Kiloan (350.000) + 10 Pcs Bed Cover (250.000) + 8 Pcs Jas (280.000) + 20 Kg Express (300.000) = 1.180.000
        $payload = [
            'customer_name' => 'Grand Hotel Express',
            'whatsapp_number' => '081987654321',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 50],
                ['service_id' => $services[3]->id, 'qty_or_weight' => 10],
                ['service_id' => $services[4]->id, 'qty_or_weight' => 8],
                ['service_id' => $services[2]->id, 'qty_or_weight' => 20],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertStatus(302);

        $order = Order::where('customer_name', 'Grand Hotel Express')->first();
        $this->assertNotNull($order);
        $this->assertEquals(1180000, $order->total_price);

        $receipt = $this->get('/nota/'.$order->id);
        $receipt->assertSee('1.180.000', false);
    }

    // =========================================================================
    // SCENARIO 5: CONCURRENT SEARCH & LOOKUP UNDER HIGH VOLUME
    // =========================================================================

    public function test_concurrent_search_and_tracking_lookup_under_order_volume(): void
    {
        $services = $this->createStandardMasterCatalog();

        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'customer_name' => "Search Target {$i}",
                'whatsapp_number' => '085555'.sprintf('%06d', $i),
                'total_price' => 15000 * $i,
                'status' => ($i % 3 == 0) ? 'Selesai' : (($i % 2 == 0) ? 'Dicuci' : 'Antrean'),
            ]);
        }

        // Test lookups for customer 7 and customer 14
        $res7 = $this->get('/?search=085555000007');
        $res7->assertStatus(200);
        $res7->assertSee('Search Target 7', false);
        $res7->assertDontSee('Search Target 14');

        $res14 = $this->get('/?search=085555000014');
        $res14->assertStatus(200);
        $res14->assertSee('Search Target 14', false);
        $res14->assertDontSee('Search Target 7');
    }

    // =========================================================================
    // SCENARIO 6: MID-DAY CATALOG EXPANSION & IMMEDIATE ORDERABILITY
    // =========================================================================

    public function test_mid_day_catalog_expansion_and_immediate_orderability(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardMasterCatalog();

        // 1. Initial morning orders
        $this->post('/pesan', [
            'customer_name' => 'Morning Customer',
            'whatsapp_number' => '081200001111',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        // 2. Admin adds special service mid-day
        $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Cuci Karpet Spesial Masjid',
            'category' => 'Satuan',
            'price_per_unit' => 18000,
            'unit_type' => 'Pcs',
            'description' => 'Paket pembersihan karpet masjid.',
        ]);
        $specialSvc = Service::where('service_name', 'Cuci Karpet Spesial Masjid')->first();
        $this->assertNotNull($specialSvc);

        // 3. Afternoon order incorporates both old and new services
        $this->post('/pesan', [
            'customer_name' => 'Afternoon Customer',
            'whatsapp_number' => '081200002222',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 3], // 21.000
                ['service_id' => $specialSvc->id, 'qty_or_weight' => 2],   // 36.000
            ],
        ]);

        $afternoonOrder = Order::where('customer_name', 'Afternoon Customer')->first();
        $this->assertEquals(57000, $afternoonOrder->total_price);
        $this->assertCount(2, $afternoonOrder->orderDetails);
    }

    // =========================================================================
    // SCENARIO 7: STATUS FILTERING UNDER REALISTIC WORKLOAD
    // =========================================================================

    public function test_order_filtering_and_pagination_under_workload(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardMasterCatalog();

        for ($i = 1; $i <= 25; $i++) {
            $status = ($i <= 10) ? 'Antrean' : (($i <= 18) ? 'Dicuci' : 'Selesai');
            Order::create([
                'customer_name' => "Workload Customer {$i}",
                'whatsapp_number' => '089900'.sprintf('%04d', $i),
                'total_price' => 10000,
                'status' => $status,
            ]);
        }

        $resAntrean = $this->actingAs($admin)->get('/admin/pesanan?status=Antrean');
        $resAntrean->assertStatus(200);
        $resAntrean->assertSee('Workload Customer 1', false);

        $resDicuci = $this->actingAs($admin)->get('/admin/pesanan?status=Dicuci');
        $resDicuci->assertStatus(200);
        $resDicuci->assertSee('Workload Customer 11', false);

        $resSelesai = $this->actingAs($admin)->get('/admin/pesanan?status=Selesai');
        $resSelesai->assertStatus(200);
        $resSelesai->assertSee('Workload Customer 20', false);
    }

    // =========================================================================
    // SCENARIO 8: END-OF-DAY AUDIT DELETION & FINANCIAL ADJUSTMENT
    // =========================================================================

    public function test_end_of_day_audit_order_deletion_and_revenue_adjustment(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardMasterCatalog();

        // 3 valid orders + 1 mistake order
        $order1 = Order::create(['customer_name' => 'Valid 1', 'whatsapp_number' => '081', 'total_price' => 50000, 'status' => 'Selesai']);
        $order2 = Order::create(['customer_name' => 'Valid 2', 'whatsapp_number' => '082', 'total_price' => 30000, 'status' => 'Selesai']);
        $orderMistake = Order::create(['customer_name' => 'Mistake Order', 'whatsapp_number' => '083', 'total_price' => 100000, 'status' => 'Antrean']);

        // Revenue before cancellation: 180.000
        $this->assertEquals(180000, Order::sum('total_price'));

        // Admin cancels / deletes the mistake order
        $this->actingAs($admin)->delete("/admin/pesanan/{$orderMistake->id}");

        // Revenue after cancellation: 80.000
        $this->assertEquals(80000, Order::sum('total_price'));

        $dashboard = $this->actingAs($admin)->get('/admin');
        $dashboard->assertSee('80.000', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to seed standard laundry services.
     */
    protected function createStandardServices(): array
    {
        return [
            Service::create([
                'service_name' => 'Cuci Komplit Reguler',
                'category' => 'Kiloan',
                'price_per_unit' => 7000,
                'unit_type' => 'Kg',
                'description' => 'Cuci, kering, setrika rapi.',
            ]),
            Service::create([
                'service_name' => 'Cuci Kering Lipat Cepat',
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
                'description' => 'Cuci dan kering lipat cepat.',
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
                'description' => 'Cuci bed cover ukuran besar.',
            ]),
        ];
    }

    /**
     * Helper to create an order with order details.
     */
    protected function createOrderWithItems(string $name, string $phone, array $items, string $status = 'Antrean'): Order
    {
        $order = Order::create([
            'customer_name' => $name,
            'whatsapp_number' => $phone,
            'total_price' => 0,
            'status' => $status,
        ]);

        $total = 0;
        foreach ($items as $item) {
            $service = $item['service'];
            $qty = $item['qty'];
            $subtotal = $service->price_per_unit * $qty;
            $total += $subtotal;

            OrderDetail::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'qty_or_weight' => $qty,
                'subtotal' => $subtotal,
            ]);
        }

        $order->total_price = $total;
        $order->save();

        return $order;
    }

    // =========================================================================
    // 1. HOMEPAGE & SERVICE CATALOG RENDERING
    // =========================================================================

    public function test_homepage_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_homepage_renders_brand_hero_and_catalog(): void
    {
        $services = $this->createStandardServices();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Laundry Express', false);
        $response->assertSee('Cuci Komplit Reguler', false);
        $response->assertSee('7.000', false);
        $response->assertSee('/ Kg', false);
        $response->assertSee('Bed Cover King Size', false);
        $response->assertSee('25.000', false);
        $response->assertSee('/ Pcs', false);
    }

    public function test_homepage_renders_order_placement_form_and_tracking_search(): void
    {
        $this->createStandardServices();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('action="'.url('/pesan').'"', false);
        $response->assertSee('customer_name', false);
        $response->assertSee('whatsapp_number', false);
        $response->assertSee('service_id', false);
        $response->assertSee('qty_or_weight', false);
        $response->assertSee('search', false);
    }

    // =========================================================================
    // 2. ORDER PLACEMENT & DATABASE TRANSACTION
    // =========================================================================

    public function test_valid_order_submission_persists_in_database_and_redirects(): void
    {
        $services = $this->createStandardServices();

        $payload = [
            'customer_name' => 'Budi Santoso',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 3], // 3 * 7000 = 21000
                ['service_id' => $services[3]->id, 'qty_or_weight' => 1], // 1 * 25000 = 25000
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('customer_name', 'Budi Santoso')->first();

        $this->assertNotNull($order);
        $response->assertRedirect('/nota/'.$order->id);
        $this->assertEquals(46000, $order->total_price);
        $this->assertEquals('Antrean', $order->status);
        $this->assertCount(2, $order->orderDetails);
    }

    public function test_order_submission_calculates_server_side_ignoring_client_tampering(): void
    {
        $services = $this->createStandardServices();

        $payload = [
            'customer_name' => 'Tamper Test User',
            'whatsapp_number' => '081299998888',
            'subtotal' => 10,
            'total_price' => 10,
            'items' => [
                [
                    'service_id' => $services[0]->id,
                    'qty_or_weight' => 2,
                    'price_per_unit' => 1,
                    'subtotal' => 2,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('customer_name', 'Tamper Test User')->first();

        $this->assertNotNull($order);
        $this->assertEquals(14000, $order->total_price); // 2 * 7000 = 14000
        $this->assertEquals(14000, $order->orderDetails->first()->subtotal);
    }

    public function test_order_submission_rejects_missing_customer_name(): void
    {
        $services = $this->createStandardServices();

        $response = $this->post('/pesan', [
            'customer_name' => '',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('customer_name');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_submission_rejects_invalid_whatsapp_number(): void
    {
        $services = $this->createStandardServices();

        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '123', // too short
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('whatsapp_number');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_submission_rejects_empty_items(): void
    {
        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '081234567890',
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_submission_rejects_zero_or_negative_quantity(): void
    {
        $services = $this->createStandardServices();

        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 0]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    // =========================================================================
    // 3. RECEIPT PAGE (NOTA) & ACTIONS
    // =========================================================================

    public function test_receipt_page_renders_order_details_and_items(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Siti Rahmawati', '081277778888', [
            ['service' => $services[0], 'qty' => 4], // 28000
            ['service' => $services[3], 'qty' => 1], // 25000
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertStatus(200);
        $response->assertSee('Siti Rahmawati', false);
        $response->assertSee('081277778888', false);
        $response->assertSee('Cuci Komplit Reguler', false);
        $response->assertSee('Bed Cover King Size', false);
        $response->assertSee('53.000', false);
        $response->assertSee('Antrean', false);
    }

    public function test_receipt_page_contains_functional_print_action_and_css(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Print User', '081233334444', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertStatus(200);
        $response->assertSee('Cetak', false);
        $response->assertSee('window.print()', false);
        $response->assertSee('d-print-none', false);

        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'fa-print') !== false || strpos($content, 'print') !== false,
            'Receipt missing print icon or descriptive identifier.'
        );
        $this->assertTrue(
            strpos($content, '@media print') !== false || strpos($content, 'd-print-none') !== false,
            'Receipt missing print media CSS.'
        );
    }

    public function test_receipt_page_contains_whatsapp_dispatch_link(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('WhatsApp User', '081255556666', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertStatus(200);
        $response->assertSee('WhatsApp', false);
        $response->assertSee('081255556666', false);

        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'wa.me') !== false || strpos($content, 'api.whatsapp.com') !== false,
            'Receipt missing wa.me link target.'
        );
        $this->assertStringContainsString('text=', $content);
        $this->assertStringContainsString((string) $order->id, $content);
    }

    public function test_receipt_page_returns_404_for_invalid_order_id(): void
    {
        $this->get('/nota/99999')->assertStatus(404);
        $this->get('/nota/-1')->assertStatus(404);
        $this->get('/nota/non-numeric-id')->assertStatus(404);
    }

    // =========================================================================
    // 4. ORDER TRACKING SEARCH & 3-STAGE PROGRESS BAR
    // =========================================================================

    public function test_tracking_search_by_whatsapp_number_finds_order(): void
    {
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Track By Phone User', '081266667777', [
            ['service' => $services[0], 'qty' => 2],
        ]);

        $response = $this->get('/?search=081266667777');
        $response->assertStatus(200);
        $response->assertSee('Track By Phone User', false);
    }

    public function test_tracking_search_by_order_id_finds_order(): void
    {
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Track By ID User', '081288889999', [
            ['service' => $services[1], 'qty' => 3],
        ]);

        $response = $this->get('/?search='.$order->id);
        $response->assertStatus(200);
        $response->assertSee('Track By ID User', false);
    }

    public function test_tracking_displays_three_stage_progress_indicators(): void
    {
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Progress Indicators User', '081211110000', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $response = $this->get('/?search=081211110000');
        $response->assertStatus(200);
        $response->assertSee('Antrean', false);
        $response->assertSee('Dicuci', false);
        $response->assertSee('Selesai', false);
    }

    public function test_tracking_accurately_reflects_status_progression(): void
    {
        $services = $this->createStandardServices();

        $orderAntrean = $this->createOrderWithItems('Status Antrean', '081210101010', [
            ['service' => $services[0], 'qty' => 1],
        ], 'Antrean');
        $this->assertEquals(33, $orderAntrean->progress_percentage);

        $orderDicuci = $this->createOrderWithItems('Status Dicuci', '081220202020', [
            ['service' => $services[0], 'qty' => 1],
        ], 'Dicuci');
        $this->assertEquals(66, $orderDicuci->progress_percentage);

        $orderSelesai = $this->createOrderWithItems('Status Selesai', '081230303030', [
            ['service' => $services[0], 'qty' => 1],
        ], 'Selesai');
        $this->assertEquals(100, $orderSelesai->progress_percentage);

        $response = $this->get('/?search=081220202020');
        $response->assertStatus(200);
        $response->assertSee('Dicuci', false);
    }

    public function test_tracking_displays_not_found_message_for_unknown_query(): void
    {
        $response = $this->get('/?search=089999999999');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertTrue(
            strpos($content, 'tidak ditemukan') !== false || strpos($content, 'Tidak ada') !== false,
            'Expected informative not found message when query yields no results.'
        );
    }

    public function test_empty_search_query_renders_homepage_cleanly(): void
    {
        $response = $this->get('/?search=');
        $response->assertStatus(200);
        $response->assertDontSee('tidak ditemukan');
    }

    public function test_tracking_escapes_xss_payloads(): void
    {
        $xss = '<script>alert("portal_xss")</script>';
        $response = $this->get('/?search='.urlencode($xss));
        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("portal_xss")</script>', false);
    }

    public function test_tracking_prevents_sql_injection(): void
    {
        $services = $this->createStandardServices();
        $this->createOrderWithItems('Hidden Customer', '081234567890', [
            ['service' => $services[0], 'qty' => 1],
        ]);

        $sqli = "' OR '1'='1";
        $response = $this->get('/?search='.urlencode($sqli));
        $response->assertStatus(200);
        $response->assertDontSee('Hidden Customer');
    }
}

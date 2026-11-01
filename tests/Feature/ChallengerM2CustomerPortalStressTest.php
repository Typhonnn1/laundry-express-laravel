<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengerM2CustomerPortalStressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up standard test services.
     */
    protected function setUpServices(): array
    {
        $this->seed(DatabaseSeeder::class);

        return [
            'kiloan1' => Service::where('service_name', 'Cuci Komplit Reguler')->first(),
            'kiloan2' => Service::where('service_name', 'Cuci Kering Lipat Cepat')->first(),
            'express' => Service::where('service_name', 'Cuci Kilat Express 3 Jam')->first(),
            'satuan1' => Service::where('service_name', 'Bed Cover King Size')->first(),
            'satuan2' => Service::where('service_name', 'Jas / Blazer Dry Cleaning')->first(),
            'satuan3' => Service::where('service_name', 'Selimut Tebal Bulu Halus')->first(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CHALLENGE DIMENSION 1: Multi-Item Baskets & Category Combinations
    |--------------------------------------------------------------------------
    */

    public function test_multi_item_basket_combining_kiloan_satuan_and_express_packages(): void
    {
        $s = $this->setUpServices();

        $basketItems = [
            ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 4], // Kiloan: 4 * 7,000 = 28,000
            ['service_id' => $s['express']->id, 'qty_or_weight' => 2], // Express: 2 * 15,000 = 30,000
            ['service_id' => $s['satuan1']->id, 'qty_or_weight' => 1], // Satuan: 1 * 25,000 = 25,000
            ['service_id' => $s['satuan2']->id, 'qty_or_weight' => 2], // Satuan: 2 * 35,000 = 70,000
            ['service_id' => $s['kiloan2']->id, 'qty_or_weight' => 3], // Kiloan: 3 * 5,000 = 15,000
        ];

        $expectedGrandTotal = 28000 + 30000 + 25000 + 70000 + 15000; // 168,000

        $response = $this->post('/pesan', [
            'customer_name' => 'Adversarial Multi-Item Customer',
            'whatsapp_number' => '081234567890',
            'items' => $basketItems,
        ]);

        $order = Order::where('customer_name', 'Adversarial Multi-Item Customer')->first();
        $this->assertNotNull($order, 'Order should be created in database.');

        $response->assertRedirect('/nota/'.$order->id);
        $this->assertEquals($expectedGrandTotal, $order->total_price);
        $this->assertCount(5, $order->orderDetails);

        // Verify category diversity
        $categoriesInOrder = $order->orderDetails->map(fn ($d) => $d->service->category)->unique()->sort()->values()->toArray();
        $this->assertEquals(['Express', 'Kiloan', 'Satuan'], $categoriesInOrder);

        // Verify unit type diversity
        $unitTypesInOrder = $order->orderDetails->map(fn ($d) => $d->service->unit_type)->unique()->sort()->values()->toArray();
        $this->assertEquals(['Kg', 'Pcs'], $unitTypesInOrder);
    }

    public function test_multi_item_basket_with_duplicate_services(): void
    {
        $s = $this->setUpServices();

        // Customer submits the same service multiple times (e.g. split loads)
        $basketItems = [
            ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 3], // 3 * 7,000 = 21,000
            ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 5], // 5 * 7,000 = 35,000
            ['service_id' => $s['satuan1']->id, 'qty_or_weight' => 1], // 1 * 25,000 = 25,000
            ['service_id' => $s['satuan1']->id, 'qty_or_weight' => 2], // 2 * 25,000 = 50,000
        ];

        $expectedGrandTotal = 21000 + 35000 + 25000 + 50000; // 131,000

        $response = $this->post('/pesan', [
            'customer_name' => 'Duplicate Services Customer',
            'whatsapp_number' => '081987654321',
            'items' => $basketItems,
        ]);

        $order = Order::where('customer_name', 'Duplicate Services Customer')->first();
        $this->assertNotNull($order);
        $this->assertEquals($expectedGrandTotal, $order->total_price);
        $this->assertCount(4, $order->orderDetails);

        $detailsForKiloan1 = $order->orderDetails->where('service_id', $s['kiloan1']->id);
        $this->assertCount(2, $detailsForKiloan1);
        $this->assertEquals(56000, $detailsForKiloan1->sum('subtotal'));
    }

    public function test_large_multi_item_basket_with_30_items(): void
    {
        $s = $this->setUpServices();
        $serviceList = array_values($s);

        $basketItems = [];
        $expectedGrandTotal = 0;

        for ($i = 0; $i < 30; $i++) {
            $service = $serviceList[$i % count($serviceList)];
            $qty = ($i % 5) + 1; // qty between 1 and 5
            $subtotal = $service->price_per_unit * $qty;
            $expectedGrandTotal += $subtotal;

            $basketItems[] = [
                'service_id' => $service->id,
                'qty_or_weight' => $qty,
            ];
        }

        $response = $this->post('/pesan', [
            'customer_name' => 'Large Basket Customer',
            'whatsapp_number' => '085566778899',
            'items' => $basketItems,
        ]);

        $order = Order::where('customer_name', 'Large Basket Customer')->first();
        $this->assertNotNull($order);
        $response->assertRedirect('/nota/'.$order->id);
        $this->assertEquals($expectedGrandTotal, $order->total_price);
        $this->assertCount(30, $order->orderDetails);
        $this->assertEquals($expectedGrandTotal, (int) $order->orderDetails->sum('subtotal'));
    }

    public function test_boundary_quantities_handling(): void
    {
        $s = $this->setUpServices();

        // 1. Minimum quantity = 1
        $responseMin = $this->post('/pesan', [
            'customer_name' => 'Min Qty Customer',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 1],
            ],
        ]);
        $orderMin = Order::where('customer_name', 'Min Qty Customer')->first();
        $this->assertNotNull($orderMin);
        $this->assertEquals(7000, $orderMin->total_price);

        // 2. High quantity within integer limits (e.g. 5,000 units of Bed Cover)
        $responseHigh = $this->post('/pesan', [
            'customer_name' => 'High Qty Customer',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $s['satuan1']->id, 'qty_or_weight' => 5000], // 5000 * 25000 = 125,000,000
            ],
        ]);
        $orderHigh = Order::where('customer_name', 'High Qty Customer')->first();
        $this->assertNotNull($orderHigh);
        $this->assertEquals(125000000, $orderHigh->total_price);

        // 3. Reject non-integer / float quantity
        $responseFloat = $this->post('/pesan', [
            'customer_name' => 'Float Qty Customer',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 2.5],
            ],
        ]);
        $responseFloat->assertSessionHasErrors('items.0.qty_or_weight');

        // 4. Reject negative quantity
        $responseNegative = $this->post('/pesan', [
            'customer_name' => 'Negative Qty Customer',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => -5],
            ],
        ]);
        $responseNegative->assertSessionHasErrors('items.0.qty_or_weight');

        // 5. Reject zero quantity
        $responseZero = $this->post('/pesan', [
            'customer_name' => 'Zero Qty Customer',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 0],
            ],
        ]);
        $responseZero->assertSessionHasErrors('items.0.qty_or_weight');
    }

    /*
    |--------------------------------------------------------------------------
    | CHALLENGE DIMENSION 2: Math Total Reconciliation Oracle & Tampering Defense
    |--------------------------------------------------------------------------
    */

    public function test_math_total_reconciliation_oracle_on_random_baskets(): void
    {
        $s = $this->setUpServices();
        $services = array_values($s);

        // Run 5 distinct randomized basket simulations
        for ($run = 1; $run <= 5; $run++) {
            $numItems = rand(2, 6);
            $itemsPayload = [];
            $expectedSubtotals = [];

            for ($j = 0; $j < $numItems; $j++) {
                $chosenService = $services[array_rand($services)];
                $qty = rand(1, 10);
                $expectedSubtotals[] = $chosenService->price_per_unit * $qty;
                $itemsPayload[] = [
                    'service_id' => $chosenService->id,
                    'qty_or_weight' => $qty,
                ];
            }

            $customerName = "Oracle Test User {$run}";
            $phone = '0812'.str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

            $this->post('/pesan', [
                'customer_name' => $customerName,
                'whatsapp_number' => $phone,
                'items' => $itemsPayload,
            ]);

            $order = Order::with('orderDetails.service')->where('customer_name', $customerName)->first();
            $this->assertNotNull($order, "Order for {$customerName} must be created.");

            // Strict Mathematical Reconciliation:
            // 1. Order total_price == Sum of all orderDetails subtotals
            $this->assertEquals(
                array_sum($expectedSubtotals),
                $order->total_price,
                "Order total_price mismatch for {$customerName}"
            );

            // 2. Sum of details in DB == order.total_price
            $this->assertEquals(
                (int) $order->orderDetails->sum('subtotal'),
                $order->total_price,
                'Order total_price must equal DB sum of order_details.subtotal'
            );

            // 3. For each detail: subtotal == qty_or_weight * service.price_per_unit
            foreach ($order->orderDetails as $idx => $detail) {
                $this->assertEquals(
                    $detail->qty_or_weight * $detail->service->price_per_unit,
                    $detail->subtotal,
                    "OrderDetail {$detail->id} subtotal must strictly equal qty * service price"
                );
            }
        }
    }

    public function test_server_side_total_recalculation_strictly_defends_against_spoofing(): void
    {
        $s = $this->setUpServices();

        // Attacker attempts to submit manipulated subtotals and grand totals
        $maliciousPayload = [
            'customer_name' => 'Price Hacker User',
            'whatsapp_number' => '081999999999',
            'subtotal' => 1,
            'total_price' => 1,
            'items' => [
                [
                    'service_id' => $s['express']->id, // Price in DB: 15,000
                    'qty_or_weight' => 4,              // Subtotal should be: 60,000
                    'price_per_unit' => 10,            // Spoofed unit price
                    'subtotal' => 40,                  // Spoofed subtotal
                ],
                [
                    'service_id' => $s['satuan2']->id, // Price in DB: 35,000
                    'qty_or_weight' => 2,              // Subtotal should be: 70,000
                    'price_per_unit' => 50,            // Spoofed unit price
                    'subtotal' => 100,                 // Spoofed subtotal
                ],
            ],
        ];

        $response = $this->post('/pesan', $maliciousPayload);

        $order = Order::where('customer_name', 'Price Hacker User')->first();
        $this->assertNotNull($order);

        // Server must completely ignore spoofed values and enforce 60,000 + 70,000 = 130,000
        $this->assertEquals(130000, $order->total_price);
        $this->assertEquals(60000, $order->orderDetails[0]->subtotal);
        $this->assertEquals(70000, $order->orderDetails[1]->subtotal);
    }

    public function test_transaction_atomicity_prevents_partial_orphans_on_failure(): void
    {
        $s = $this->setUpServices();

        $initialOrdersCount = Order::count();
        $initialDetailsCount = OrderDetail::count();

        // 3 valid items, 1 invalid service_id
        $payloadWithInvalidService = [
            'customer_name' => 'Atomic Transaction User',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 2],
                ['service_id' => $s['express']->id, 'qty_or_weight' => 1],
                ['service_id' => 999999, 'qty_or_weight' => 3], // Does not exist
            ],
        ];

        $response = $this->post('/pesan', $payloadWithInvalidService);
        $response->assertSessionHasErrors('items.2.service_id');

        // Zero records should have been persisted
        $this->assertEquals($initialOrdersCount, Order::count());
        $this->assertEquals($initialDetailsCount, OrderDetail::count());
    }

    /*
    |--------------------------------------------------------------------------
    | CHALLENGE DIMENSION 3: Receipt Page (Nota) & WhatsApp Payload Structure
    |--------------------------------------------------------------------------
    */

    public function test_receipt_page_accurate_rendering_and_actions(): void
    {
        $s = $this->setUpServices();

        $response = $this->post('/pesan', [
            'customer_name' => 'Ratna Dewi',
            'whatsapp_number' => '081345678901',
            'items' => [
                ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 3], // 21,000
                ['service_id' => $s['satuan1']->id, 'qty_or_weight' => 2], // 50,000
            ],
        ]);

        $order = Order::where('customer_name', 'Ratna Dewi')->first();
        $this->assertNotNull($order);

        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);

        // 1. Customer metadata
        $receiptResponse->assertSee('Ratna Dewi', false);
        $receiptResponse->assertSee('081345678901', false);
        $receiptResponse->assertSee('#LE-'.str_pad($order->id, 5, '0', STR_PAD_LEFT), false);
        $receiptResponse->assertSee('Antrean', false);

        // 2. Itemized details
        $receiptResponse->assertSee('Cuci Komplit Reguler', false);
        $receiptResponse->assertSee('3 Kg', false);
        $receiptResponse->assertSee('21.000', false);
        $receiptResponse->assertSee('Bed Cover King Size', false);
        $receiptResponse->assertSee('2 Pcs', false);
        $receiptResponse->assertSee('50.000', false);

        // 3. Grand total
        $receiptResponse->assertSee('71.000', false);

        // 4. Print action
        $receiptResponse->assertSee('window.print()', false);
        $receiptResponse->assertSee('Cetak Struk', false);
        $receiptResponse->assertSee('d-print-none', false);
    }

    public function test_whatsapp_payload_url_structure_and_encoded_content(): void
    {
        $s = $this->setUpServices();

        $response = $this->post('/pesan', [
            'customer_name' => 'Hendro Prasetyo',
            'whatsapp_number' => '087812345678',
            'items' => [
                ['service_id' => $s['express']->id, 'qty_or_weight' => 2], // 30,000
                ['service_id' => $s['satuan3']->id, 'qty_or_weight' => 1], // 20,000
            ],
        ]);

        $order = Order::where('customer_name', 'Hendro Prasetyo')->first();
        $this->assertNotNull($order);

        $receiptResponse = $this->get('/nota/'.$order->id);
        $content = $receiptResponse->getContent();

        // 1. Phone number normalization: 0878... -> 62878...
        $expectedWaPhone = '627812345678';
        // Note: 0878... -> '62' . substr('0878...', 1) = '6287812345678'
        $expectedNormalizedPhone = '6287812345678';

        $this->assertStringContainsString('https://wa.me/'.$expectedNormalizedPhone.'?text=', $content);

        // 2. Extract and decode the raw WhatsApp payload text
        preg_match('/href="https:\/\/wa\.me\/[0-9]+\?text=([^"]+)"/', $content, $matches);
        $this->assertNotEmpty($matches, 'WhatsApp URL href attribute must match pattern.');

        $rawEncodedText = $matches[1];

        // Verify RFC 3986 percent-encoding (spaces encoded as %20, not raw spaces)
        $this->assertStringNotContainsString(' ', $rawEncodedText, 'URL parameter must not contain raw whitespace.');
        $this->assertStringContainsString('%20', $rawEncodedText, 'URL parameter must be percent-encoded with %20.');

        $decodedText = rawurldecode($rawEncodedText);

        // 3. Strict verification of decoded message components
        $this->assertStringContainsString('*NOTA PESANAN - LAUNDRY EXPRESS*', $decodedText);
        $this->assertStringContainsString("No. Nota: #{$order->id}", $decodedText);
        $this->assertStringContainsString('Pelanggan: Hendro Prasetyo', $decodedText);
        $this->assertStringContainsString('WhatsApp: 087812345678', $decodedText);
        $this->assertStringContainsString('Status: Antrean', $decodedText);
        $this->assertStringContainsString('*Rincian Layanan:*', $decodedText);
        $this->assertStringContainsString('Cuci Kilat Express 3 Jam (2 Kg)', $decodedText);
        $this->assertStringContainsString('Selimut Tebal Bulu Halus (1 Pcs)', $decodedText);
        $this->assertStringContainsString('*Total Tagihan: Rp 50.000*', $decodedText);
        $this->assertStringContainsString(url('/?search='.$order->id), $decodedText);
        $this->assertStringContainsString('Laundry Express', $decodedText);
    }

    public function test_whatsapp_phone_number_normalization_variants(): void
    {
        $s = $this->setUpServices();

        $phoneVariants = [
            '081234567890' => '6281234567890',
            '+6281234567890' => '6281234567890',
            '6281234567890' => '6281234567890',
        ];

        foreach ($phoneVariants as $inputPhone => $expectedTargetPhone) {
            $name = "WA Variant {$expectedTargetPhone}";

            $this->post('/pesan', [
                'customer_name' => $name,
                'whatsapp_number' => $inputPhone,
                'items' => [
                    ['service_id' => $s['kiloan1']->id, 'qty_or_weight' => 1],
                ],
            ]);

            $order = Order::where('customer_name', $name)->first();
            $this->assertNotNull($order);

            $response = $this->get('/nota/'.$order->id);
            $content = $response->getContent();

            $this->assertStringContainsString(
                "https://wa.me/{$expectedTargetPhone}?text=",
                $content,
                "Failed normalizing phone {$inputPhone} to {$expectedTargetPhone}"
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHALLENGE DIMENSION 4: 3-Stage Progress Bar Mapping & Order Tracking
    |--------------------------------------------------------------------------
    */

    public function test_progress_bar_percentage_and_dom_attributes_for_all_three_stages(): void
    {
        $s = $this->setUpServices();

        $stages = [
            Order::STATUS_ANTREAN => [
                'percentage' => 33,
                'badge' => 'warning text-dark',
                'icon' => 'fa-clock',
                'narrative' => 'antrean',
            ],
            Order::STATUS_DICUCI => [
                'percentage' => 66,
                'badge' => 'info text-dark',
                'icon' => 'fa-jug-detergent',
                'narrative' => 'pencucian',
            ],
            Order::STATUS_SELESAI => [
                'percentage' => 100,
                'badge' => 'success',
                'icon' => 'fa-circle-check',
                'narrative' => 'selesai',
            ],
        ];

        foreach ($stages as $status => $expected) {
            $order = Order::create([
                'customer_name' => "Stage User {$status}",
                'whatsapp_number' => '0812'.rand(10000000, 99999999),
                'total_price' => 14000,
                'status' => $status,
            ]);

            // Model attribute tests
            $this->assertEquals($expected['percentage'], $order->progress_percentage);
            $this->assertEquals($expected['badge'], $order->status_badge_class);

            // Web tracking search tests
            $response = $this->get('/?search='.$order->id);
            $response->assertStatus(200);

            $content = $response->getContent();

            // Progress bar width & aria attributes
            $this->assertStringContainsString("style=\"width: {$expected['percentage']}%;\"", $content);
            $this->assertStringContainsString("aria-valuenow=\"{$expected['percentage']}\"", $content);

            // Status label & badge
            $this->assertStringContainsString($status, $content);

            // Narrative text
            $this->assertStringContainsStringIgnoringCase($expected['narrative'], $content);
        }
    }

    public function test_tracking_search_variants_resolve_correct_order(): void
    {
        $s = $this->setUpServices();

        $order = Order::create([
            'customer_name' => 'Tracking Variants Customer',
            'whatsapp_number' => '081298765432',
            'total_price' => 35000,
            'status' => Order::STATUS_DICUCI,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $s['satuan2']->id,
            'qty_or_weight' => 1,
            'subtotal' => 35000,
        ]);

        $searchVariants = [
            (string) $order->id,                     // Plain numeric ID (e.g. "3")
            '#'.$order->id,                         // Prefixed ID (e.g. "#3")
            '#LE-'.str_pad($order->id, 5, '0', STR_PAD_LEFT), // Full invoice code (e.g. "#LE-00003")
            '081298765432',                          // WhatsApp phone number
            '  081298765432  ',                      // Phone with whitespace padding
        ];

        foreach ($searchVariants as $query) {
            $response = $this->get('/?search='.urlencode($query));
            $response->assertStatus(200);
            $response->assertSee('Tracking Variants Customer', false);
            $response->assertSee('style="width: 66%;"', false);
            $response->assertSee('Dicuci', false);
        }
    }

    public function test_dynamic_status_update_immediately_reflects_in_progress_bar(): void
    {
        $s = $this->setUpServices();

        $order = Order::create([
            'customer_name' => 'Transition Test Customer',
            'whatsapp_number' => '081255554444',
            'total_price' => 25000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        // Stage 1: Antrean (33%)
        $res1 = $this->get('/?search='.$order->id);
        $res1->assertSee('style="width: 33%;"', false);

        // Transition to Stage 2: Dicuci (66%)
        $order->update(['status' => Order::STATUS_DICUCI]);
        $res2 = $this->get('/?search='.$order->id);
        $res2->assertSee('style="width: 66%;"', false);

        // Transition to Stage 3: Selesai (100%)
        $order->update(['status' => Order::STATUS_SELESAI]);
        $res3 = $this->get('/?search='.$order->id);
        $res3->assertSee('style="width: 100%;"', false);
    }
}

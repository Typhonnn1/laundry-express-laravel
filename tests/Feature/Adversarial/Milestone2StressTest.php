<?php

namespace Tests\Feature\Adversarial;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Milestone 2 Adversarial Stress Test Suite
 *
 * Exhaustive empirical testing covering:
 * 1. Price tampering attacks (manipulated subtotal, total_price, external discount fields)
 * 2. Malformed quantities and weights (zero, negative, non-integer, overflow boundaries)
 * 3. Non-existent service IDs & transaction rollback atomicity
 * 4. XSS injection attacks in customer_name, receipt, tracking views & WhatsApp links
 * 5. Tracking search boundaries, numeric ID vs phone collisions, SQLi vectors, and edge formatting
 */
class Milestone2StressTest extends TestCase
{
    use RefreshDatabase;

    protected Service $regulerKiloan;

    protected Service $expressKiloan;

    protected Service $satuanBedCover;

    protected function setUp(): void
    {
        parent::setUp();

        $this->regulerKiloan = Service::create([
            'service_name' => 'Cuci Komplit Reguler',
            'category' => 'Kiloan',
            'price_per_unit' => 7000,
            'unit_type' => 'Kg',
            'description' => 'Cuci, kering, dan setrika rapi.',
        ]);

        $this->expressKiloan = Service::create([
            'service_name' => 'Cuci Kilat Express 3 Jam',
            'category' => 'Express',
            'price_per_unit' => 15000,
            'unit_type' => 'Kg',
            'description' => 'Layanan prioritas 3 jam selesai.',
        ]);

        $this->satuanBedCover = Service::create([
            'service_name' => 'Bed Cover King Size',
            'category' => 'Satuan',
            'price_per_unit' => 25000,
            'unit_type' => 'Pcs',
            'description' => 'Cuci bed cover tebal wangi.',
        ]);
    }

    // =========================================================================
    // 1. PRICE TAMPERING ATTACK VECTORS
    // =========================================================================

    public function test_tampered_total_price_zero_in_post_is_ignored_and_recalculated(): void
    {
        $payload = [
            'customer_name' => 'Hacker Zero Price',
            'whatsapp_number' => '081299990001',
            'total_price' => 0, // Injected free price
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 5, // 5 * 7000 = 35,000
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990001')->first();

        $this->assertNotNull($order);
        $response->assertRedirect('/nota/'.$order->id);

        // Server MUST ignore client total_price and calculate 35,000
        $this->assertEquals(35000, $order->total_price);
        $this->assertNotEquals(0, $order->total_price);
    }

    public function test_tampered_negative_total_price_in_post_is_ignored(): void
    {
        $payload = [
            'customer_name' => 'Hacker Negative Price',
            'whatsapp_number' => '081299990002',
            'total_price' => -99999999, // Injected negative price
            'items' => [
                [
                    'service_id' => $this->expressKiloan->id,
                    'qty_or_weight' => 2, // 2 * 15000 = 30,000
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990002')->first();

        $this->assertNotNull($order);
        $response->assertRedirect('/nota/'.$order->id);
        $this->assertEquals(30000, $order->total_price);
    }

    public function test_tampered_item_subtotal_in_post_is_ignored_and_strictly_recomputed(): void
    {
        $payload = [
            'customer_name' => 'Hacker Subtotal Tamper',
            'whatsapp_number' => '081299990003',
            'items' => [
                [
                    'service_id' => $this->satuanBedCover->id,
                    'qty_or_weight' => 2, // 2 * 25,000 = 50,000
                    'subtotal' => 100, // Injected fake subtotal
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990003')->first();

        $this->assertNotNull($order);
        $detail = $order->orderDetails()->first();

        $this->assertNotNull($detail);
        $this->assertEquals(50000, $detail->subtotal);
        $this->assertNotEquals(100, $detail->subtotal);
        $this->assertEquals(50000, $order->total_price);
    }

    public function test_tampered_extra_financial_fields_discount_or_tax_are_discarded(): void
    {
        $payload = [
            'customer_name' => 'Hacker Extra Fields',
            'whatsapp_number' => '081299990004',
            'discount' => 50000,
            'is_free' => true,
            'override_price' => 1,
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 3, // 3 * 7000 = 21,000
                    'custom_rate' => 10,
                ],
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990004')->first();

        $this->assertNotNull($order);
        $this->assertEquals(21000, $order->total_price);
    }

    public function test_multi_item_order_accumulates_accurate_grand_total_across_different_unit_types(): void
    {
        $payload = [
            'customer_name' => 'Budi Mixed Basket',
            'whatsapp_number' => '081299990005',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 4, // 4 * 7000 = 28,000
                ],
                [
                    'service_id' => $this->expressKiloan->id,
                    'qty_or_weight' => 2, // 2 * 15000 = 30,000
                ],
                [
                    'service_id' => $this->satuanBedCover->id,
                    'qty_or_weight' => 3, // 3 * 25000 = 75,000
                ],
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990005')->first();

        $this->assertNotNull($order);
        $expectedTotal = 28000 + 30000 + 75000; // 133,000
        $this->assertEquals($expectedTotal, $order->total_price);
        $this->assertCount(3, $order->orderDetails);
    }

    // =========================================================================
    // 2. MALFORMED QUANTITIES AND WEIGHTS
    // =========================================================================

    public function test_zero_quantity_is_rejected_with_validation_error(): void
    {
        $payload = [
            'customer_name' => 'Zero Qty Test',
            'whatsapp_number' => '081299990010',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 0,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertEquals(0, Order::count());
    }

    public function test_negative_quantity_is_rejected_with_validation_error(): void
    {
        $payload = [
            'customer_name' => 'Negative Qty Test',
            'whatsapp_number' => '081299990011',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => -5,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertEquals(0, Order::count());
    }

    public function test_floating_point_quantity_is_rejected_as_non_integer(): void
    {
        $payload = [
            'customer_name' => 'Float Qty Test',
            'whatsapp_number' => '081299990012',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 3.75,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertEquals(0, Order::count());
    }

    public function test_string_non_numeric_quantity_is_rejected(): void
    {
        $payload = [
            'customer_name' => 'String Qty Test',
            'whatsapp_number' => '081299990013',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 'five_kg',
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertEquals(0, Order::count());
    }

    public function test_extremely_large_quantity_exceeding_max_limit_is_rejected(): void
    {
        $payload = [
            'customer_name' => 'Overflow Qty Test',
            'whatsapp_number' => '081299990014',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 100001, // Limit is 100,000
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertEquals(0, Order::count());
    }

    public function test_upper_boundary_quantity_within_max_limit_is_accepted_safely(): void
    {
        $payload = [
            'customer_name' => 'Max Allowed Qty Test',
            'whatsapp_number' => '081299990015',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 100000, // Exactly at max limit: 100,000 * 7,000 = 700,000,000
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990015')->first();

        $this->assertNotNull($order);
        $response->assertRedirect('/nota/'.$order->id);
        $this->assertEquals(700000000, $order->total_price);
    }

    public function test_empty_items_collection_is_rejected(): void
    {
        $payload = [
            'customer_name' => 'Empty Items Test',
            'whatsapp_number' => '081299990016',
            'items' => [],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items']);
        $this->assertEquals(0, Order::count());
    }

    public function test_missing_items_payload_is_rejected(): void
    {
        $payload = [
            'customer_name' => 'Missing Items Test',
            'whatsapp_number' => '081299990017',
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items']);
        $this->assertEquals(0, Order::count());
    }

    // =========================================================================
    // 3. NON-EXISTENT SERVICE IDS & TRANSACTION ROLLBACK ATOMICITY
    // =========================================================================

    public function test_non_existent_service_id_is_rejected_by_validation(): void
    {
        $payload = [
            'customer_name' => 'Invalid Service Test',
            'whatsapp_number' => '081299990020',
            'items' => [
                [
                    'service_id' => 999999, // Non-existent ID
                    'qty_or_weight' => 2,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.service_id']);
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, OrderDetail::count());
    }

    public function test_mixed_valid_and_invalid_service_ids_rejects_and_prevents_partial_order_creation(): void
    {
        $payload = [
            'customer_name' => 'Partial Invalid Service Test',
            'whatsapp_number' => '081299990021',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id, // Valid
                    'qty_or_weight' => 3,
                ],
                [
                    'service_id' => 888888, // Invalid
                    'qty_or_weight' => 2,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.1.service_id']);

        // Verify strict atomicity: NO order or order_detail record created
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, OrderDetail::count());
    }

    public function test_transaction_rollback_preserves_database_integrity_on_unexpected_failure(): void
    {
        // Verify DB transaction behavior: if an exception happens after order insertion,
        // transaction rolls back the created order completely.
        try {
            DB::transaction(function () {
                $order = Order::create([
                    'customer_name' => 'Crash Test Dummy',
                    'whatsapp_number' => '081299990022',
                    'total_price' => 0,
                    'status' => Order::STATUS_ANTREAN,
                ]);

                OrderDetail::create([
                    'order_id' => $order->id,
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 1,
                    'subtotal' => 7000,
                ]);

                // Simulate catastrophic mid-transaction failure
                throw new \RuntimeException('Simulated mid-transaction failure');
            });
        } catch (\RuntimeException $e) {
            // Expected simulation exception
        }

        $this->assertEquals(0, Order::where('whatsapp_number', '081299990022')->count());
        $this->assertEquals(0, OrderDetail::count());
    }

    // =========================================================================
    // 4. XSS INJECTION & HTML SANITIZATION VERIFICATION
    // =========================================================================

    public function test_script_tag_in_customer_name_is_safely_escaped_in_receipt_view(): void
    {
        $xssPayload = '<script>alert("XSS_EXPRESS")</script>';

        $payload = [
            'customer_name' => $xssPayload,
            'whatsapp_number' => '081299990030',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 2,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990030')->first();
        $this->assertNotNull($order);

        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);

        // Raw unescaped script tag MUST NOT appear in the rendered HTML
        $receiptResponse->assertDontSee($xssPayload, false);

        // The HTML-escaped entity representation MUST appear
        $escapedPayload = htmlspecialchars($xssPayload, ENT_QUOTES, 'UTF-8');
        $receiptResponse->assertSee($escapedPayload, false);
    }

    public function test_img_onerror_in_customer_name_is_safely_escaped_in_receipt_view(): void
    {
        $xssPayload = '<img src=x onerror=alert("HACKED")>';

        $payload = [
            'customer_name' => $xssPayload,
            'whatsapp_number' => '081299990031',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 1,
                ],
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990031')->first();
        $this->assertNotNull($order);

        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);

        $receiptResponse->assertDontSee($xssPayload, false);
        $escaped = htmlspecialchars($xssPayload, ENT_QUOTES, 'UTF-8');
        $receiptResponse->assertSee($escaped, false);
    }

    public function test_xss_in_customer_name_is_safely_escaped_in_tracking_view(): void
    {
        $xssPayload = '<svg onload=alert(document.cookie)>';

        $payload = [
            'customer_name' => $xssPayload,
            'whatsapp_number' => '081299990032',
            'items' => [
                [
                    'service_id' => $this->satuanBedCover->id,
                    'qty_or_weight' => 1,
                ],
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990032')->first();
        $this->assertNotNull($order);

        // Search in homepage tracking
        $trackingResponse = $this->get('/?search=081299990032');
        $trackingResponse->assertStatus(200);

        // Raw malicious SVG tag must not be present
        $trackingResponse->assertDontSee($xssPayload, false);

        // HTML-escaped content must be present
        $escaped = htmlspecialchars($xssPayload, ENT_QUOTES, 'UTF-8');
        $trackingResponse->assertSee($escaped, false);
    }

    public function test_xss_in_search_query_parameter_is_escaped_in_not_found_alert(): void
    {
        $maliciousSearch = '"><script>alert("SearchXSS")</script>';

        $response = $this->get('/?search='.urlencode($maliciousSearch));
        $response->assertStatus(200);

        // Raw unescaped injection must not break the HTML input or alert tag
        $response->assertDontSee($maliciousSearch, false);

        // Safe escaped text should be displayed
        $response->assertSee(htmlspecialchars($maliciousSearch, ENT_QUOTES, 'UTF-8'), false);
    }

    public function test_whatsapp_link_in_receipt_safely_urlencodes_customer_details(): void
    {
        $specialName = 'O\'Connor & Sons <VIP>';
        $payload = [
            'customer_name' => $specialName,
            'whatsapp_number' => '081299990033',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 2,
                ],
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081299990033')->first();

        $response = $this->get('/nota/'.$order->id);
        $response->assertStatus(200);

        // Check that wa.me URL has rawurlencode applied
        $encodedNameInUrl = rawurlencode($specialName);
        $response->assertSee($encodedNameInUrl, false);
    }

    // =========================================================================
    // 5. TRACKING LOOKUP BOUNDARIES, NUMERIC COLLISIONS & SQL INJECTION
    // =========================================================================

    public function test_tracking_by_exact_whatsapp_number(): void
    {
        $order = Order::create([
            'customer_name' => 'Dewi Lestari',
            'whatsapp_number' => '081277770001',
            'total_price' => 35000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $response = $this->get('/?search=081277770001');
        $response->assertStatus(200);
        $response->assertSee('Dewi Lestari');
        $response->assertSee('#LE-'.str_pad($order->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('Antrean');
    }

    public function test_tracking_by_formatted_order_id_with_hash(): void
    {
        $order = Order::create([
            'customer_name' => 'Rahmat Hidayat',
            'whatsapp_number' => '081277770002',
            'total_price' => 50000,
            'status' => Order::STATUS_DICUCI,
        ]);

        $response = $this->get('/?search=%23'.$order->id); // e.g. #1
        $response->assertStatus(200);
        $response->assertSee('Rahmat Hidayat');
        $response->assertSee('Dicuci');
    }

    public function test_tracking_by_formatted_le_prefix_order_id(): void
    {
        $order = Order::create([
            'customer_name' => 'Fajar Pratama',
            'whatsapp_number' => '081277770003',
            'total_price' => 45000,
            'status' => Order::STATUS_SELESAI,
        ]);

        $searchCode = '#LE-'.str_pad($order->id, 5, '0', STR_PAD_LEFT);
        $response = $this->get('/?search='.urlencode($searchCode));
        $response->assertStatus(200);
        $response->assertSee('Fajar Pratama');
        $response->assertSee('Selesai');
    }

    public function test_tracking_non_existent_phone_displays_not_found_alert(): void
    {
        $response = $this->get('/?search=089999999999');
        $response->assertStatus(200);
        $response->assertSee('Pesanan tidak ditemukan');
        $response->assertSee('089999999999');
    }

    public function test_tracking_whitespace_only_query_does_not_trigger_search_results(): void
    {
        $response = $this->get('/?search=%20%20%20');
        $response->assertStatus(200);

        // Should not show "Pesanan tidak ditemukan" warning because search was empty/whitespace
        $response->assertDontSee('Pesanan tidak ditemukan');
    }

    public function test_tracking_multiple_orders_for_same_customer_are_all_returned_latest_first(): void
    {
        $firstOrder = Order::create([
            'customer_name' => 'Repeat Customer',
            'whatsapp_number' => '081277770004',
            'total_price' => 20000,
            'status' => Order::STATUS_SELESAI,
        ]);
        $firstOrder->timestamps = false;
        $firstOrder->created_at = now()->subDays(2);
        $firstOrder->save();

        $secondOrder = Order::create([
            'customer_name' => 'Repeat Customer',
            'whatsapp_number' => '081277770004',
            'total_price' => 40000,
            'status' => Order::STATUS_ANTREAN,
        ]);
        $secondOrder->timestamps = false;
        $secondOrder->created_at = now();
        $secondOrder->save();

        $response = $this->get('/?search=081277770004');
        $response->assertStatus(200);
        $response->assertSee('Repeat Customer');

        $trackedOrders = $response->viewData('trackedOrders');
        $this->assertCount(2, $trackedOrders);
        $this->assertEquals($secondOrder->id, $trackedOrders->first()->id);
        $this->assertEquals($firstOrder->id, $trackedOrders->last()->id);
    }

    public function test_tracking_sql_injection_attempt_is_treated_as_literal_without_error(): void
    {
        Order::create([
            'customer_name' => 'Legit User',
            'whatsapp_number' => '081277770005',
            'total_price' => 15000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $sqliPayloads = [
            "' OR '1'='1",
            "999999' OR '1'='1 --",
            '999999; DROP TABLE orders; --',
            "' UNION SELECT * FROM users --",
        ];

        foreach ($sqliPayloads as $sqli) {
            $response = $this->get('/?search='.urlencode($sqli));
            $response->assertStatus(200);
            $response->assertSee('Pesanan tidak ditemukan');
        }

        // Verify database remains completely intact
        $this->assertEquals(1, Order::where('whatsapp_number', '081277770005')->count());
        $this->assertTrue(Order::exists());
    }

    public function test_tracking_extremely_long_search_term_handled_gracefully(): void
    {
        $longQuery = str_repeat('9', 1000);
        $response = $this->get('/?search='.$longQuery);
        $response->assertStatus(200);
        $response->assertSee('Pesanan tidak ditemukan');
    }

    // =========================================================================
    // 6. CUSTOMER NAME & PHONE NUMBER INPUT VALIDATION BOUNDARIES
    // =========================================================================

    public function test_whitespace_only_customer_name_is_rejected(): void
    {
        $payload = [
            'customer_name' => '     ', // Spaces only
            'whatsapp_number' => '081299990040',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 1,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['customer_name']);
        $this->assertEquals(0, Order::count());
    }

    public function test_customer_name_exceeding_100_chars_is_rejected(): void
    {
        $payload = [
            'customer_name' => str_repeat('A', 101),
            'whatsapp_number' => '081299990041',
            'items' => [
                [
                    'service_id' => $this->regulerKiloan->id,
                    'qty_or_weight' => 1,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['customer_name']);
        $this->assertEquals(0, Order::count());
    }

    public function test_invalid_phone_formats_are_rejected(): void
    {
        $invalidPhones = [
            '12345678',       // Doesn't start with 0 or 62
            '+12345678901',   // International non-Indonesian
            '081234',         // Too short (< 8 chars)
            '0812345678901234567890123', // Too long (> 20 chars)
            '0812-ABCD-EFGH', // Contains letters and hyphens without regex support
            '<script>phone',  // Injection string
        ];

        foreach ($invalidPhones as $phone) {
            $payload = [
                'customer_name' => 'Test Phone Validation',
                'whatsapp_number' => $phone,
                'items' => [
                    [
                        'service_id' => $this->regulerKiloan->id,
                        'qty_or_weight' => 1,
                    ],
                ],
            ];

            $response = $this->post('/pesan', $payload);
            $response->assertSessionHasErrors(['whatsapp_number']);
        }

        $this->assertEquals(0, Order::count());
    }

    public function test_valid_phone_formats_accepted(): void
    {
        $validPhones = [
            '08123456789',
            '628123456789',
            '+628123456789',
        ];

        foreach ($validPhones as $index => $phone) {
            $payload = [
                'customer_name' => 'Valid Customer '.$index,
                'whatsapp_number' => $phone,
                'items' => [
                    [
                        'service_id' => $this->regulerKiloan->id,
                        'qty_or_weight' => 1,
                    ],
                ],
            ];

            $response = $this->post('/pesan', $payload);
            $order = Order::where('whatsapp_number', $phone)->first();
            $this->assertNotNull($order);
            $response->assertRedirect('/nota/'.$order->id);
        }

        $this->assertEquals(count($validPhones), Order::count());
    }

    // =========================================================================
    // 7. RECEIPT VIEW BOUNDARIES
    // =========================================================================

    public function test_non_existent_receipt_returns_404(): void
    {
        $response = $this->get('/nota/999999');
        $response->assertStatus(404);
    }

    public function test_alphanumeric_order_id_in_nota_route_returns_404(): void
    {
        $response = $this->get('/nota/not-a-number');
        $response->assertStatus(404);
    }
}

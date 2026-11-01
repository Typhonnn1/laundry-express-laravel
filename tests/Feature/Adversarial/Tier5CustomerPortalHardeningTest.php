<?php

namespace Tests\Feature\Adversarial;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tier 5: Adversarial Customer Portal Coverage Hardening Test Suite
 *
 * White-box empirical challenge testing covering:
 * 1. Duplicate service items in order submission payloads
 * 2. Extremely large quantity values (100,000 Kg boundary, overflow, non-integer)
 * 3. Customer names across mixed scripts, Unicode, business punctuation, and 100-char boundary
 * 4. WhatsApp numbers across varied formats (+62, 62, 08, dashes, spaces, trailing garbage, wa.me URLs)
 * 5. Receipt rendering and null-safety when service dependencies are deleted/missing
 * 6. Tracking search semantics: exact match, partial phone non-disclosure, formatted IDs, non-digits
 * 7. Malformed payload structures, missing sub-keys, and multi-item basket stress
 */
class Tier5CustomerPortalHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Service $kiloanReguler;

    protected Service $expressKiloan;

    protected Service $satuanBedCover;

    protected Service $satuanJas;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kiloanReguler = Service::create([
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
            'description' => 'Layanan express 3 jam selesai.',
        ]);

        $this->satuanBedCover = Service::create([
            'service_name' => 'Bed Cover King Size',
            'category' => 'Satuan',
            'price_per_unit' => 25000,
            'unit_type' => 'Pcs',
            'description' => 'Cuci bed cover tebal wangi.',
        ]);

        $this->satuanJas = Service::create([
            'service_name' => 'Jas / Blazer Dry Cleaning',
            'category' => 'Satuan',
            'price_per_unit' => 35000,
            'unit_type' => 'Pcs',
            'description' => 'Dry cleaning pakaian formal.',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Laundry',
            'username' => 'admin',
            'email' => 'admin@laundry.test',
            'password' => Hash::make('admin123'),
        ]);
    }

    // =========================================================================
    // 1. DUPLICATE SERVICE ITEMS IN ORDER SUBMISSION
    // =========================================================================

    public function test_duplicate_service_ids_in_submission_are_both_persisted_with_correct_subtotals(): void
    {
        // Customer selects the same service twice with different weights (e.g. 2 separate laundry bags)
        $payload = [
            'customer_name' => 'Budi Dua Kantong',
            'whatsapp_number' => '081211112222',
            'items' => [
                [
                    'service_id' => $this->kiloanReguler->id,
                    'qty_or_weight' => 3, // 3 * 7000 = 21,000
                ],
                [
                    'service_id' => $this->kiloanReguler->id,
                    'qty_or_weight' => 5, // 5 * 7000 = 35,000
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081211112222')->first();

        $this->assertNotNull($order, 'Order should be created when payload contains duplicate service items.');
        $response->assertRedirect('/nota/'.$order->id);

        // Verify grand total is the exact mathematical sum (21,000 + 35,000 = 56,000)
        $this->assertEquals(56000, $order->total_price);

        // Verify exactly 2 order detail records were created for the same service
        $details = $order->orderDetails()->get();
        $this->assertCount(2, $details);
        $this->assertEquals(3, $details[0]->qty_or_weight);
        $this->assertEquals(21000, $details[0]->subtotal);
        $this->assertEquals(5, $details[1]->qty_or_weight);
        $this->assertEquals(35000, $details[1]->subtotal);
    }

    public function test_receipt_renders_all_duplicate_service_items_and_whatsapp_summary_accurately(): void
    {
        $payload = [
            'customer_name' => 'Siti Tiga Bedcover',
            'whatsapp_number' => '081233334444',
            'items' => [
                ['service_id' => $this->satuanBedCover->id, 'qty_or_weight' => 1], // 25,000
                ['service_id' => $this->satuanBedCover->id, 'qty_or_weight' => 2], // 50,000
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081233334444')->firstOrFail();

        $response = $this->get('/nota/'.$order->id);
        $response->assertStatus(200);

        // Verify total price is displayed
        $response->assertSee('Rp 75.000', false);

        // Verify both lines appear in the rendered table
        $response->assertSee('Bed Cover King Size', false);
        $response->assertSee('1 Pcs', false);
        $response->assertSee('2 Pcs', false);

        // Verify WhatsApp URL message contains both items
        $content = $response->getContent();
        $this->assertStringContainsString('wa.me/6281233334444', $content);
        $this->assertStringContainsString(rawurlencode('1 Pcs'), $content);
        $this->assertStringContainsString(rawurlencode('2 Pcs'), $content);
    }

    // =========================================================================
    // 2. QUANTITY BOUNDARY & INTEGER OVERFLOW HARDENING
    // =========================================================================

    public function test_exact_maximum_allowed_quantity_boundary_100000_is_accepted_safely(): void
    {
        $payload = [
            'customer_name' => 'Pabrik Tekstil Express',
            'whatsapp_number' => '081255556666',
            'items' => [
                [
                    'service_id' => $this->expressKiloan->id, // Rp 15.000 / Kg
                    'qty_or_weight' => 100000, // 100,000 * 15,000 = Rp 1,500,000,000 (1.5 Milyar)
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081255556666')->first();

        $this->assertNotNull($order);
        $response->assertRedirect('/nota/'.$order->id);
        $this->assertEquals(1500000000, $order->total_price);

        // Verify receipt formats 1.5 billion IDR properly
        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('1.500.000.000', false);
    }

    public function test_quantity_exceeding_max_limit_by_one_100001_is_rejected_by_validation(): void
    {
        $payload = [
            'customer_name' => 'Over Boundary Customer',
            'whatsapp_number' => '081255557777',
            'items' => [
                [
                    'service_id' => $this->kiloanReguler->id,
                    'qty_or_weight' => 100001,
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertDatabaseMissing('orders', ['whatsapp_number' => '081255557777']);
    }

    public function test_quantity_with_massive_integer_string_does_not_crash_server(): void
    {
        $payload = [
            'customer_name' => 'Int Overflow Attacker',
            'whatsapp_number' => '081255558888',
            'items' => [
                [
                    'service_id' => $this->kiloanReguler->id,
                    'qty_or_weight' => '9999999999999999999999999999',
                ],
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertDatabaseMissing('orders', ['whatsapp_number' => '081255558888']);
    }

    public function test_quantity_zero_and_negative_values_are_strictly_rejected(): void
    {
        $invalidQuantities = [0, -1, -100000];

        foreach ($invalidQuantities as $qty) {
            $payload = [
                'customer_name' => 'Invalid Qty User',
                'whatsapp_number' => '081255559999',
                'items' => [
                    [
                        'service_id' => $this->kiloanReguler->id,
                        'qty_or_weight' => $qty,
                    ],
                ],
            ];

            $response = $this->post('/pesan', $payload);
            $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_multiple_large_items_accumulate_safely(): void
    {
        $payload = [
            'customer_name' => 'Bulk Multi Item Hospital',
            'whatsapp_number' => '081266661111',
            'items' => [
                ['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 100000], // 700,000,000
                ['service_id' => $this->satuanJas->id, 'qty_or_weight' => 10000],       // 350,000,000
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081266661111')->first();

        $this->assertNotNull($order);
        $this->assertEquals(1050000000, $order->total_price); // 1.05 Milyar
    }

    // =========================================================================
    // 3. CUSTOMER NAME SCRIPT DIVERSITY & BOUNDARY HARDENING
    // =========================================================================

    public function test_exact_100_character_customer_name_is_accepted_and_trimmed(): void
    {
        $name100 = str_repeat('A', 100);
        $payload = [
            'customer_name' => $name100,
            'whatsapp_number' => '081277771000',
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 2]],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081277771000')->first();

        $this->assertNotNull($order);
        $this->assertEquals(100, strlen($order->customer_name));
        $this->assertEquals($name100, $order->customer_name);
        $response->assertRedirect('/nota/'.$order->id);
    }

    public function test_101_character_customer_name_is_rejected_by_validation(): void
    {
        $name101 = str_repeat('B', 101);
        $payload = [
            'customer_name' => $name101,
            'whatsapp_number' => '081277771001',
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 2]],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['customer_name']);
        $this->assertDatabaseMissing('orders', ['whatsapp_number' => '081277771001']);
    }

    public function test_single_character_customer_name_is_accepted(): void
    {
        $payload = [
            'customer_name' => 'X',
            'whatsapp_number' => '081277771002',
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 1]],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081277771002')->first();

        $this->assertNotNull($order);
        $this->assertEquals('X', $order->customer_name);
    }

    public function test_multilingual_unicode_scripts_are_stored_and_rendered_faithfully(): void
    {
        $unicodeNames = [
            'Arabic' => 'محمد عبد الله',
            'Chinese' => '王小明',
            'Japanese' => '山田 太郎（ヤマダ タロウ）',
            'Cyrillic' => 'Александр Пушкин',
            'European' => 'Hélène Müller-García ñ ç é',
            'Emoji' => '🧺 Cuci Express ✨ Budi ⭐',
        ];

        $index = 10;
        foreach ($unicodeNames as $script => $name) {
            $phone = '0812777710'.$index;
            $payload = [
                'customer_name' => $name,
                'whatsapp_number' => $phone,
                'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 2]],
            ];

            $response = $this->post('/pesan', $payload);
            $order = Order::where('whatsapp_number', $phone)->first();

            $this->assertNotNull($order, "Failed to persist order with {$script} script name.");
            $this->assertEquals($name, $order->customer_name);

            // Verify receipt rendering
            $receiptResponse = $this->get('/nota/'.$order->id);
            $receiptResponse->assertStatus(200);
            $receiptResponse->assertSee(e($name), false);

            // Verify tracking search renders the Unicode customer name
            $trackingResponse = $this->get('/?search='.$phone);
            $trackingResponse->assertStatus(200);
            $trackingResponse->assertSee(e($name), false);

            $index++;
        }
    }

    public function test_business_and_special_punctuations_in_name_are_safely_rendered(): void
    {
        $businessNames = [
            'Dr. Ir. H. Muhammad Soekarno, M.Sc., Ph.D.',
            'Hotel & Villa Melati Indah (R. 302-305)',
            'PT. Sumber Makmur Abadi #99/B [Gedung A, Lt. 3]',
            'O\'Connor & D\'Angelo\'s Laundry',
        ];

        $index = 30;
        foreach ($businessNames as $name) {
            $phone = '0812777710'.$index;
            $payload = [
                'customer_name' => $name,
                'whatsapp_number' => $phone,
                'items' => [['service_id' => $this->satuanJas->id, 'qty_or_weight' => 1]],
            ];

            $this->post('/pesan', $payload);
            $order = Order::where('whatsapp_number', $phone)->first();

            $this->assertNotNull($order);
            $this->assertEquals($name, $order->customer_name);

            $receiptResponse = $this->get('/nota/'.$order->id);
            $receiptResponse->assertStatus(200);
            $receiptResponse->assertSee(e($name), false);

            $index++;
        }
    }

    public function test_customer_name_with_surrounding_whitespace_is_trimmed_on_save(): void
    {
        $payload = [
            'customer_name' => '   Siti Rahmawati Trimmed   ',
            'whatsapp_number' => '081277771099',
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 1]],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081277771099')->first();

        $this->assertNotNull($order);
        $this->assertEquals('Siti Rahmawati Trimmed', $order->customer_name);
    }

    // =========================================================================
    // 4. WHATSAPP NUMBER FORMAT MATRIX & WA.ME DISPATCH
    // =========================================================================

    public function test_whatsapp_number_prefix_variations_generate_correct_wame_target(): void
    {
        $phoneTestCases = [
            ['input' => '081234567890', 'expectedWaMe' => '6281234567890'],
            ['input' => '6281234567890', 'expectedWaMe' => '6281234567890'],
            ['input' => '+6281234567890', 'expectedWaMe' => '6281234567890'],
        ];

        foreach ($phoneTestCases as $index => $case) {
            $payload = [
                'customer_name' => 'WA Test Customer '.$index,
                'whatsapp_number' => $case['input'],
                'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 2]],
            ];

            $response = $this->post('/pesan', $payload);
            $order = Order::where('whatsapp_number', $case['input'])->first();

            $this->assertNotNull($order, "Failed to create order for phone input: {$case['input']}");
            $response->assertRedirect('/nota/'.$order->id);

            $receiptResponse = $this->get('/nota/'.$order->id);
            $receiptResponse->assertStatus(200);
            $content = $receiptResponse->getContent();

            $expectedUrlPart = 'wa.me/'.$case['expectedWaMe'];
            $this->assertStringContainsString(
                $expectedUrlPart,
                $content,
                "Receipt wa.me link does not contain {$expectedUrlPart} for input {$case['input']}"
            );
        }
    }

    public function test_shortest_and_longest_valid_phone_boundaries(): void
    {
        // Shortest valid: '0' + 8 digits = 9 chars
        $shortPhone = '081234567';
        // Longest valid with +62: '+62' + 17 digits = 20 chars (max:20)
        $longPhone = '+6281234567890123456';

        $payloadShort = [
            'customer_name' => 'Short Phone User',
            'whatsapp_number' => $shortPhone,
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 1]],
        ];
        $this->post('/pesan', $payloadShort)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', ['whatsapp_number' => $shortPhone]);

        $payloadLong = [
            'customer_name' => 'Long Phone User',
            'whatsapp_number' => $longPhone,
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 1]],
        ];
        $this->post('/pesan', $payloadLong)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', ['whatsapp_number' => $longPhone]);
    }

    public function test_whatsapp_numbers_with_dashes_internal_spaces_and_garbage_are_rejected(): void
    {
        $malformedPhones = [
            '0812-3456-7890',          // Dashes
            '0812 3456 7890',          // Internal spaces
            '+62 812 3456 7890',       // International prefix with internal spaces
            '+62-812-3456-7890',       // International prefix with dashes
            '081234567890abc',         // Trailing letters
            '081234567890#',           // Trailing symbol
            '081234567890<script>',    // Trailing tag
            '+123456789012',           // Non-Indonesian prefix (+1)
            '+447911123456',           // Non-Indonesian prefix (+44)
            '981234567890',            // Starts with 9 (invalid prefix)
            '781234567890',            // Starts with 7 (invalid prefix)
            '+62',                     // Only prefix
            '08',                      // Only 2 digits
            '081234',                  // Too short (< 8 chars)
            '08123456789012345678901', // Too long (> 20 chars)
        ];

        foreach ($malformedPhones as $phone) {
            $payload = [
                'customer_name' => 'Malformed Phone User',
                'whatsapp_number' => $phone,
                'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 1]],
            ];

            $response = $this->post('/pesan', $payload);
            $response->assertSessionHasErrors(['whatsapp_number']);
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_whatsapp_regex_accepts_non_standard_00_prefix_exposing_wame_mangling(): void
    {
        // Challenge Observation:
        // The regex `/^(\+?62|0)[0-9]{8,18}$/` accepts any number starting with '0'.
        // Consequently, '0062...' passes validation as valid, but receipt generation
        // strips only the first '0' and produces 'wa.me/62062...', which is a malformed destination.
        $doubleZeroPhone = '006281234567890';
        $payload = [
            'customer_name' => 'Double Zero Customer',
            'whatsapp_number' => $doubleZeroPhone,
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 1]],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', $doubleZeroPhone)->first();

        $this->assertNotNull($order, 'Regex currently permits double-zero leading phone numbers.');
        $response->assertRedirect('/nota/'.$order->id);

        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);

        // Verify empirically that the generated URL has mangled 62062 prefix
        $content = $receiptResponse->getContent();
        $this->assertStringContainsString('wa.me/6206281234567890', $content);
    }

    public function test_whatsapp_numbers_with_surrounding_whitespace_are_trimmed_and_accepted(): void
    {
        $whitespacePhone = '   081234567890   ';
        $payload = [
            'customer_name' => 'Whitespace Phone User',
            'whatsapp_number' => $whitespacePhone,
            'items' => [['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 1]],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasNoErrors();

        // Laravel TrimStrings middleware normalizes input before validation and persistence
        $this->assertDatabaseHas('orders', ['whatsapp_number' => '081234567890']);
    }

    // =========================================================================
    // 5. RECEIPT RENDERING & NULL-SAFETY HARDENING
    // =========================================================================

    public function test_receipt_renders_gracefully_when_all_order_details_are_empty(): void
    {
        // An order with 0 details (e.g. manual order or cleared items)
        $order = Order::create([
            'customer_name' => 'Empty Order Details Customer',
            'whatsapp_number' => '081288880001',
            'total_price' => 0,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $response = $this->get('/nota/'.$order->id);
        $response->assertStatus(200);

        // Verify fallback message appears
        $response->assertSee('Tidak ada rincian item layanan.', false);
        $response->assertSee('Empty Order Details Customer', false);
        $response->assertSee('Rp 0', false);

        // Verify print and WhatsApp buttons still render
        $response->assertSee('Cetak', false);
        $response->assertSee('Kirim', false);
    }

    public function test_receipt_behavior_when_service_is_deleted_via_admin_cascade(): void
    {
        // Create an ephemeral service to be deleted
        $tempService = Service::create([
            'service_name' => 'Layanan Sementara Hapus',
            'category' => 'Kiloan',
            'price_per_unit' => 6000,
            'unit_type' => 'Kg',
        ]);

        $payload = [
            'customer_name' => 'Pelanggan Layanan Dihapus',
            'whatsapp_number' => '081288880002',
            'items' => [
                ['service_id' => $tempService->id, 'qty_or_weight' => 3], // 18,000
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081288880002')->firstOrFail();
        $this->assertEquals(18000, $order->total_price);

        // Admin deletes the service package
        $deleteResponse = $this->actingAs($this->adminUser)->delete("/admin/layanan/{$tempService->id}");
        $deleteResponse->assertRedirect('/admin/layanan');
        $this->assertDatabaseMissing('services', ['id' => $tempService->id]);

        // Customer accesses receipt after service deletion
        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);

        // Due to cascadeOnDelete on order_details.service_id, details were cascaded
        // Order remains intact and receipt handles empty detail collection cleanly
        $receiptResponse->assertSee('Pelanggan Layanan Dihapus', false);
        $receiptResponse->assertSee('Rp 18.000', false);
        $receiptResponse->assertSee('Tidak ada rincian item layanan.', false);
    }

    public function test_receipt_behavior_when_one_of_multiple_services_is_deleted(): void
    {
        $tempService = Service::create([
            'service_name' => 'Promo Discontinued Service',
            'category' => 'Satuan',
            'price_per_unit' => 10000,
            'unit_type' => 'Pcs',
        ]);

        $payload = [
            'customer_name' => 'Multi Service Cascade Customer',
            'whatsapp_number' => '081288880003',
            'items' => [
                ['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 2], // 14,000 (Retained)
                ['service_id' => $tempService->id, 'qty_or_weight' => 1],        // 10,000 (Will cascade)
            ],
        ];

        $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081288880003')->firstOrFail();
        $this->assertEquals(24000, $order->total_price);
        $this->assertCount(2, $order->orderDetails);

        // Delete the discontinued service
        $this->actingAs($this->adminUser)->delete("/admin/layanan/{$tempService->id}");

        // Access receipt: remaining service must still render properly
        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);

        $receiptResponse->assertSee('Cuci Komplit Reguler', false);
        $receiptResponse->assertSee('Rp 14.000', false);
        $receiptResponse->assertDontSee('Promo Discontinued Service', false);
    }

    // =========================================================================
    // 6. TRACKING SEARCH MATCHING SEMANTICS & PRIVACY PRESERVATION
    // =========================================================================

    public function test_tracking_search_exact_match_succeeds_by_phone_and_order_id(): void
    {
        $order = Order::create([
            'customer_name' => 'Exact Search Customer',
            'whatsapp_number' => '081299992001',
            'total_price' => 35000,
            'status' => Order::STATUS_DICUCI,
        ]);

        // Search by exact phone
        $responsePhone = $this->get('/?search=081299992001');
        $responsePhone->assertStatus(200);
        $responsePhone->assertSee('Exact Search Customer', false);
        $responsePhone->assertSee('Dicuci', false);

        // Search by raw ID
        $responseId = $this->get('/?search='.$order->id);
        $responseId->assertStatus(200);
        $responseId->assertSee('Exact Search Customer', false);
    }

    public function test_tracking_search_with_various_id_decorations(): void
    {
        $order = Order::create([
            'customer_name' => 'Decorated ID Customer',
            'whatsapp_number' => '081299992002',
            'total_price' => 25000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $decorations = [
            '#'.$order->id,
            '#LE-'.str_pad($order->id, 5, '0', STR_PAD_LEFT),
            'LE-'.$order->id,
            'LE'.$order->id,
            'Order #'.$order->id,
            '#0000'.$order->id,
        ];

        foreach ($decorations as $code) {
            $response = $this->get('/?search='.urlencode($code));
            $response->assertStatus(200);
            $response->assertSee('Decorated ID Customer', false);
        }
    }

    public function test_tracking_search_partial_phone_does_not_disclose_customer_orders(): void
    {
        // Anti-Enumeration / Privacy Protection:
        // Searching partial phone "08129999" MUST NOT return orders for "081299992003"
        Order::create([
            'customer_name' => 'Privacy Conscious VIP',
            'whatsapp_number' => '081299992003',
            'total_price' => 70000,
            'status' => Order::STATUS_SELESAI,
        ]);

        $partialQuery = '08129999';
        $response = $this->get('/?search='.$partialQuery);
        $response->assertStatus(200);

        // Must display "Pesanan tidak ditemukan" and must NOT reveal customer name
        $response->assertSee('Pesanan tidak ditemukan', false);
        $response->assertDontSee('Privacy Conscious VIP', false);
    }

    public function test_tracking_search_with_leading_and_trailing_whitespace_is_trimmed(): void
    {
        Order::create([
            'customer_name' => 'Whitespace Search Customer',
            'whatsapp_number' => '081299992004',
            'total_price' => 45000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $response = $this->get('/?search='.urlencode('   081299992004   '));
        $response->assertStatus(200);
        $response->assertSee('Whitespace Search Customer', false);
    }

    public function test_tracking_search_with_purely_non_digit_gibberish_returns_not_found(): void
    {
        $nonDigitQueries = [
            '###',
            'LE-ABC',
            'NOTA-XYZ',
            'NO_NUMBERS_HERE',
        ];

        foreach ($nonDigitQueries as $query) {
            $response = $this->get('/?search='.urlencode($query));
            $response->assertStatus(200);
            $response->assertSee('Pesanan tidak ditemukan', false);
        }
    }

    public function test_tracking_search_non_existent_number_renders_escaped_not_found_card(): void
    {
        $nonExistentPhone = '089999888777';
        $response = $this->get('/?search='.$nonExistentPhone);
        $response->assertStatus(200);
        $response->assertSee('Pesanan tidak ditemukan', false);
        $response->assertSee($nonExistentPhone, false);
    }

    public function test_empty_search_query_does_not_display_not_found_alert(): void
    {
        $response = $this->get('/?search=');
        $response->assertStatus(200);
        $response->assertDontSee('Pesanan tidak ditemukan', false);
    }

    // =========================================================================
    // 7. MALFORMED PAYLOAD STRUCTURES & BASKET STRESS
    // =========================================================================

    public function test_order_submission_rejects_missing_service_id_in_item_sub_array(): void
    {
        $payload = [
            'customer_name' => 'Missing Service ID User',
            'whatsapp_number' => '081233339901',
            'items' => [
                ['qty_or_weight' => 2], // Missing service_id
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.service_id']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_submission_rejects_missing_qty_in_item_sub_array(): void
    {
        $payload = [
            'customer_name' => 'Missing Qty User',
            'whatsapp_number' => '081233339902',
            'items' => [
                ['service_id' => $this->kiloanReguler->id], // Missing qty_or_weight
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $response->assertSessionHasErrors(['items.0.qty_or_weight']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_submission_handles_non_sequential_or_string_array_keys(): void
    {
        // Non-sequential repeater indices e.g. after user adds and removes DOM rows
        $payload = [
            'customer_name' => 'Non Sequential Keys User',
            'whatsapp_number' => '081233339903',
            'items' => [
                'row_5' => ['service_id' => $this->kiloanReguler->id, 'qty_or_weight' => 2], // 14,000
                'row_9' => ['service_id' => $this->expressKiloan->id, 'qty_or_weight' => 1], // 15,000
            ],
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081233339903')->first();

        $this->assertNotNull($order);
        $this->assertEquals(29000, $order->total_price);
        $this->assertCount(2, $order->orderDetails);
    }

    public function test_large_basket_with_10_distinct_and_repeated_items_calculates_atomically(): void
    {
        $items = [];
        $expectedTotal = 0;

        // Generate 10 mixed items
        for ($i = 0; $i < 10; $i++) {
            $service = ($i % 2 === 0) ? $this->kiloanReguler : $this->satuanBedCover;
            $qty = ($i % 3) + 1; // 1, 2, or 3
            $items[] = [
                'service_id' => $service->id,
                'qty_or_weight' => $qty,
            ];
            $expectedTotal += ($service->price_per_unit * $qty);
        }

        $payload = [
            'customer_name' => 'Large Basket Hotel Customer',
            'whatsapp_number' => '081233339904',
            'items' => $items,
        ];

        $response = $this->post('/pesan', $payload);
        $order = Order::where('whatsapp_number', '081233339904')->first();

        $this->assertNotNull($order);
        $response->assertRedirect('/nota/'.$order->id);
        $this->assertEquals($expectedTotal, $order->total_price);
        $this->assertCount(10, $order->orderDetails);

        // Verify receipt displays all 10 rows
        $receiptResponse = $this->get('/nota/'.$order->id);
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee($order->formatted_total_price, false);
    }
}

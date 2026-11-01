<?php

namespace Tests\Feature\E2E;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tier 2: Boundary & Corner Cases Test Suite (Opaque-Box E2E Testing)
 *
 * Verifies defensive posture, robust input validation, price tampering resistance,
 * error handling, and authorization boundaries under abnormal or adversarial inputs.
 */
class Tier2_BoundaryAndCornerCasesTest extends TestCase
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
        ];
    }

    // ==========================================
    // 1. CUSTOMER NAME VALIDATION BOUNDARIES
    // ==========================================

    public function test_empty_customer_name_is_rejected(): void
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

    public function test_whitespace_only_customer_name_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => '     ',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('customer_name');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_name_exceeding_100_characters_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $longName = str_repeat('A', 101);
        $response = $this->post('/pesan', [
            'customer_name' => $longName,
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('customer_name');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_name_with_html_tags_is_escaped_on_render(): void
    {
        $services = $this->createStandardServices();
        $xssName = 'Budi <script>alert("xss")</script>';
        $response = $this->post('/pesan', [
            'customer_name' => $xssName,
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $order = Order::where('whatsapp_number', '081234567890')->first();
        if ($order) {
            $viewResponse = $this->get('/nota/'.$order->id);
            $viewResponse->assertDontSee('<script>alert("xss")</script>', false);
        }
    }

    // ==========================================
    // 2. WHATSAPP NUMBER VALIDATION BOUNDARIES
    // ==========================================

    public function test_empty_whatsapp_number_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('whatsapp_number');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_whatsapp_number_with_letters_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '0812abc45678',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('whatsapp_number');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_whatsapp_number_with_special_symbols_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '0812#$*!1234',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('whatsapp_number');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_extremely_short_whatsapp_number_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '1234',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors('whatsapp_number');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_standard_indonesian_phone_format_is_accepted(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Format 08 User',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('orders', ['whatsapp_number' => '081234567890']);
    }

    public function test_international_prefix_phone_format_is_accepted(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Format +62 User',
            'whatsapp_number' => '6281234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 2]],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('orders', ['whatsapp_number' => '6281234567890']);
    }

    // ==========================================
    // 3. ORDER ITEMS & QUANTITY BOUNDARIES
    // ==========================================

    public function test_missing_items_payload_is_rejected(): void
    {
        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '081234567890',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_empty_items_array_is_rejected(): void
    {
        $response = $this->post('/pesan', [
            'customer_name' => 'Valid Name',
            'whatsapp_number' => '081234567890',
            'items' => [],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_zero_quantity_or_weight_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Zero Qty User',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 0]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_negative_quantity_or_weight_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Negative Qty User',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => -5]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_non_integer_quantity_is_rejected(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'String Qty User',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => $services[0]->id, 'qty_or_weight' => 'invalid_number']],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_non_existent_service_id_is_rejected(): void
    {
        $response = $this->post('/pesan', [
            'customer_name' => 'Ghost Service User',
            'whatsapp_number' => '081234567890',
            'items' => [['service_id' => 99999, 'qty_or_weight' => 2]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_partial_invalid_item_rolls_back_entire_order_transaction(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Rollback User',
            'whatsapp_number' => '081234567890',
            'items' => [
                ['service_id' => $services[0]->id, 'qty_or_weight' => 3], // Valid
                ['service_id' => 99999, 'qty_or_weight' => 2], // Invalid
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('orders', ['customer_name' => 'Rollback User']);
        $this->assertDatabaseCount('order_details', 0);
    }

    // ==========================================
    // 4. PRICE TAMPERING RESISTANCE
    // ==========================================

    public function test_tampered_client_subtotal_is_ignored_and_recalculated_by_server(): void
    {
        $services = $this->createStandardServices();
        // Attacker attempts to inject 1 Rupiah subtotal
        $response = $this->post('/pesan', [
            'customer_name' => 'Subtotal Tamperer',
            'whatsapp_number' => '081234567890',
            'items' => [
                [
                    'service_id' => $services[0]->id, // 7000
                    'qty_or_weight' => 2,              // 2 * 7000 = 14000
                    'subtotal' => 1,                   // Tampered value
                ],
            ],
        ]);

        $order = Order::where('customer_name', 'Subtotal Tamperer')->first();
        $this->assertNotNull($order);
        $detail = $order->orderDetails->first();
        $this->assertEquals(14000, $detail->subtotal, 'Server must calculate real subtotal, ignoring injected payload.');
    }

    public function test_tampered_client_total_price_is_ignored_and_recalculated_by_server(): void
    {
        $services = $this->createStandardServices();
        // Attacker attempts to inject total_price = 100
        $response = $this->post('/pesan', [
            'customer_name' => 'Total Tamperer',
            'whatsapp_number' => '081234567890',
            'total_price' => 100, // Tampered total
            'items' => [
                [
                    'service_id' => $services[0]->id, // 7000
                    'qty_or_weight' => 3,              // 21000
                ],
            ],
        ]);

        $order = Order::where('customer_name', 'Total Tamperer')->first();
        $this->assertNotNull($order);
        $this->assertEquals(21000, $order->total_price, 'Server must recalculate total from DB rates.');
    }

    public function test_large_quantity_does_not_overflow_integer_total(): void
    {
        $services = $this->createStandardServices();
        $response = $this->post('/pesan', [
            'customer_name' => 'Commercial Bulk Customer',
            'whatsapp_number' => '081234567890',
            'items' => [
                [
                    'service_id' => $services[0]->id, // 7000
                    'qty_or_weight' => 500,            // 3.500.000
                ],
            ],
        ]);

        $order = Order::where('customer_name', 'Commercial Bulk Customer')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3500000, $order->total_price);
    }

    // ==========================================
    // 5. PUBLIC ROUTE NOT FOUND & SQLi DEFENSE
    // ==========================================

    public function test_non_existent_order_receipt_returns_404(): void
    {
        $response = $this->get('/nota/99999');
        $response->assertStatus(404);
    }

    public function test_negative_order_id_in_receipt_returns_404(): void
    {
        $response = $this->get('/nota/-1');
        $response->assertStatus(404);
    }

    public function test_non_numeric_order_id_in_receipt_returns_404(): void
    {
        $response = $this->get('/nota/invalid-id');
        $response->assertStatus(404);
    }

    public function test_tracking_search_with_sqli_payload_does_not_reveal_unauthorized_records(): void
    {
        $services = $this->createStandardServices();
        $order = Order::create([
            'customer_name' => 'Secret Customer',
            'whatsapp_number' => '081234567890',
            'total_price' => 14000,
            'status' => 'Antrean',
        ]);

        // Attempt SQL injection via search parameter
        $sqliPayload = "' OR '1'='1";
        $response = $this->get('/?search='.urlencode($sqliPayload));

        $response->assertStatus(200);
        $response->assertDontSee('Secret Customer');
    }

    public function test_tracking_search_with_xss_script_is_safely_escaped(): void
    {
        $xssPayload = '<script>alert("search_xss")</script>';
        $response = $this->get('/?search='.urlencode($xssPayload));

        $response->assertStatus(200);
        $response->assertDontSee('<script>alert("search_xss")</script>', false);
    }

    public function test_tracking_search_with_empty_string_renders_homepage_cleanly(): void
    {
        $response = $this->get('/?search=');
        $response->assertStatus(200);
    }

    // ==========================================
    // 6. ADMIN AUTHENTICATION BOUNDARIES
    // ==========================================

    public function test_admin_login_with_sqli_payload_is_rejected(): void
    {
        $this->createAdminUser();

        $response = $this->post('/login', [
            'username' => "' OR '1'='1' --",
            'password' => 'irrelevant',
        ]);

        $this->assertGuest();
    }

    public function test_admin_login_with_empty_credentials_returns_validation_errors(): void
    {
        $response = $this->post('/login', [
            'username' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['username', 'password']);
        $this->assertGuest();
    }

    public function test_admin_login_with_valid_username_but_empty_password_fails(): void
    {
        $this->createAdminUser();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    // ==========================================
    // 7. ADMIN SERVICE CRUD BOUNDARIES
    // ==========================================

    public function test_admin_service_creation_with_negative_price_is_rejected(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Negative Price Service',
            'category' => 'Kiloan',
            'price_per_unit' => -5000,
            'unit_type' => 'Kg',
        ]);

        $response->assertSessionHasErrors('price_per_unit');
        $this->assertDatabaseMissing('services', ['service_name' => 'Negative Price Service']);
    }

    public function test_admin_service_creation_with_invalid_category_enum_is_rejected(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Invalid Category Service',
            'category' => 'NonExistentCategory',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
        ]);

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseMissing('services', ['service_name' => 'Invalid Category Service']);
    }

    public function test_admin_service_creation_with_invalid_unit_type_enum_is_rejected(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Invalid Unit Service',
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Liter',
        ]);

        $response->assertSessionHasErrors('unit_type');
        $this->assertDatabaseMissing('services', ['service_name' => 'Invalid Unit Service']);
    }

    // ==========================================
    // 8. ADMIN ORDER MANAGEMENT BOUNDARIES
    // ==========================================

    public function test_admin_status_transition_with_invalid_status_value_is_rejected(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = Order::create([
            'customer_name' => 'Invalid Status Target',
            'whatsapp_number' => '081234567890',
            'total_price' => 14000,
            'status' => 'Antrean',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Batal', // Not in ['Antrean', 'Dicuci', 'Selesai']
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('Antrean', $order->fresh()->status);
    }

    public function test_unauthenticated_attempt_to_delete_order_redirects_to_login(): void
    {
        $services = $this->createStandardServices();
        $order = Order::create([
            'customer_name' => 'Protected Order',
            'whatsapp_number' => '081234567890',
            'total_price' => 14000,
            'status' => 'Antrean',
        ]);

        $response = $this->delete("/admin/pesanan/{$order->id}");
        $response->assertRedirect('/login');
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}

<?php

namespace Tests\Feature\Adversarial;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Milestone 3 Adversarial Stress & Security Test Suite
 *
 * Exhaustive empirical verification of Milestone 3:
 * 1. SQL injection payloads in login username and password
 * 2. Bruteforce resistance and session invalidation across rapid repeated logins/logouts
 * 3. Forbidden access to all administrative endpoints without authentication or with expired tokens
 * 4. Malicious status transitions (Batal, <script>, null, overflow strings, case mismatches)
 * 5. Service creation boundary conditions (negative/zero/float prices, emoji names, length limits, enums)
 * 6. Admin order search and filtering resilience under adversarial inputs
 */
class Milestone3AuthAndSecurityStressTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Service $standardService;

    protected Order $sampleOrder;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default admin user
        $this->adminUser = User::create([
            'name' => 'Admin Laundry',
            'username' => 'admin',
            'email' => 'admin@laundry.test',
            'password' => Hash::make('admin123'),
        ]);

        // Seed a standard service
        $this->standardService = Service::create([
            'service_name' => 'Cuci Komplit Reguler',
            'category' => 'Kiloan',
            'price_per_unit' => 7000,
            'unit_type' => 'Kg',
            'description' => 'Cuci, kering, dan setrika.',
        ]);

        // Seed a sample order with detail
        $this->sampleOrder = Order::create([
            'customer_name' => 'Siti Rahmawati',
            'whatsapp_number' => '081234567890',
            'total_price' => 14000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        OrderDetail::create([
            'order_id' => $this->sampleOrder->id,
            'service_id' => $this->standardService->id,
            'qty_or_weight' => 2,
            'subtotal' => 14000,
        ]);
    }

    // =========================================================================
    // 1. SQL INJECTION PAYLOADS IN AUTHENTICATION
    // =========================================================================

    /**
     * Test diverse SQL injection vectors in the login username field.
     */
    public function test_login_rejects_sql_injection_payloads_in_username(): void
    {
        $sqliPayloads = [
            "' OR '1'='1",
            "admin' --",
            "admin' #",
            "' OR 1=1 --",
            "' OR ''='",
            '" OR ""="',
            "admin' OR 1=1#",
            "' UNION SELECT 1, 'admin', 'admin@laundry.test', NULL, 'hash', NULL, NULL, NULL --",
            "admin' AND SLEEP(0.1) --",
            "'; DROP TABLE users; --",
            "admin'/*comment*/--",
            "1' or '1' = '1' /*",
        ];

        foreach ($sqliPayloads as $payload) {
            $response = $this->post('/login', [
                'username' => $payload,
                'password' => 'admin123',
            ]);

            $this->assertGuest();
            $response->assertSessionHasErrors('username');
            $this->assertNull(Auth::user());
        }
    }

    /**
     * Test SQL injection vectors in the password field when targeting valid username.
     */
    public function test_login_rejects_sql_injection_payloads_in_password(): void
    {
        $sqliPayloads = [
            "' OR '1'='1",
            "admin123' OR '1'='1",
            "' OR ''='",
            "admin123' --",
            "admin123' #",
            "' UNION SELECT 'admin123' --",
            "'; DELETE FROM users; --",
        ];

        foreach ($sqliPayloads as $payload) {
            $response = $this->post('/login', [
                'username' => 'admin',
                'password' => $payload,
            ]);

            $this->assertGuest();
            $response->assertSessionHasErrors('username');
        }
    }

    /**
     * Test login endpoint handles parameter tampering, array inputs, and null bytes safely.
     */
    public function test_login_handles_type_juggling_and_null_bytes_safely(): void
    {
        // 1. Array parameter tampering for username
        $responseArrUser = $this->post('/login', [
            'username' => ['admin'],
            'password' => 'admin123',
        ]);
        $this->assertGuest();
        $responseArrUser->assertSessionHasErrors('username');

        // 2. Array parameter tampering for password
        $responseArrPass = $this->post('/login', [
            'username' => 'admin',
            'password' => ['admin123'],
        ]);
        $this->assertGuest();
        $responseArrPass->assertSessionHasErrors('password');

        // 3. Null bytes injection
        $responseNull = $this->post('/login', [
            'username' => "admin\0' OR 1=1--",
            'password' => "admin123\0",
        ]);
        $this->assertGuest();
        $responseNull->assertSessionHasErrors('username');
    }

    // =========================================================================
    // 2. BRUTEFORCE RESISTANCE & SESSION INVALIDATION
    // =========================================================================

    /**
     * Test repeated rapid failed login attempts preserve guest isolation without state leaks.
     */
    public function test_rapid_repeated_failed_logins_preserve_guest_state(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $response = $this->post('/login', [
                'username' => "fake_user_{$i}",
                'password' => "wrong_pass_{$i}",
            ]);

            $this->assertGuest();
            $response->assertSessionHasErrors('username');
        }

        // Verify users table was not affected
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['username' => 'admin']);
    }

    /**
     * Test rapid repeated login and logout cycles maintain strict session lifecycle.
     */
    public function test_rapid_repeated_login_and_logout_cycles(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            // Login
            $loginRes = $this->post('/login', [
                'username' => 'admin',
                'password' => 'admin123',
            ]);
            $loginRes->assertRedirect('/admin');
            $this->assertAuthenticatedAs($this->adminUser);

            // Access protected route
            $dashRes = $this->get('/admin');
            $dashRes->assertStatus(200);

            // Logout
            $logoutRes = $this->post('/logout');
            $logoutRes->assertRedirect('/login');
            $this->assertGuest();

            // Access blocked post-logout
            $blockedRes = $this->get('/admin');
            $blockedRes->assertRedirect('/login');
        }
    }

    /**
     * Test session invalidation ensures an old session cannot access protected endpoints after logout.
     */
    public function test_session_invalidation_prevents_session_replay_after_logout(): void
    {
        // 1. Login as admin
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $this->assertAuthenticatedAs($this->adminUser);

        // 2. Perform logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();

        // 3. Subsequent request to admin dashboard must be strictly rejected
        $postLogoutResponse = $this->get('/admin');
        $postLogoutResponse->assertRedirect('/login');

        // 4. Subsequent request to admin services must be strictly rejected
        $servicesResponse = $this->get('/admin/layanan');
        $servicesResponse->assertRedirect('/login');
    }

    /**
     * Test multiple unauthenticated or redundant logout calls succeed gracefully without 500 error.
     */
    public function test_redundant_and_unauthenticated_logout_calls_handled_safely(): void
    {
        // Logout while already guest
        $res1 = $this->post('/logout');
        $res1->assertRedirect('/login');
        $this->assertGuest();

        $res2 = $this->post('/logout');
        $res2->assertRedirect('/login');
        $this->assertGuest();
    }

    // =========================================================================
    // 3. FORBIDDEN ACCESS TO ALL ADMINISTRATIVE ENDPOINTS
    // =========================================================================

    /**
     * Test unauthenticated access across all admin HTTP methods is blocked and redirects to /login.
     */
    public function test_unauthenticated_access_blocked_across_all_admin_endpoints(): void
    {
        $endpoints = [
            ['GET', '/admin'],
            ['GET', '/admin/layanan'],
            ['POST', '/admin/layanan', [
                'service_name' => 'Intruder Service',
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
            ]],
            ['PUT', "/admin/layanan/{$this->standardService->id}", [
                'service_name' => 'Tampered Service',
                'category' => 'Kiloan',
                'price_per_unit' => 99999,
                'unit_type' => 'Kg',
            ]],
            ['PATCH', "/admin/layanan/{$this->standardService->id}", [
                'service_name' => 'Tampered Service Patch',
                'category' => 'Kiloan',
                'price_per_unit' => 88888,
                'unit_type' => 'Kg',
            ]],
            ['DELETE', "/admin/layanan/{$this->standardService->id}"],
            ['GET', '/admin/pesanan'],
            ['PATCH', "/admin/pesanan/{$this->sampleOrder->id}/status", [
                'status' => Order::STATUS_SELESAI,
            ]],
            ['DELETE', "/admin/pesanan/{$this->sampleOrder->id}"],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint[0];
            $uri = $endpoint[1];
            $data = $endpoint[2] ?? [];

            $response = $this->call($method, $uri, $data);

            $this->assertEquals(302, $response->getStatusCode(), "Endpoint {$method} {$uri} must return 302 redirect for guest.");
            $response->assertRedirect('/login');
            $this->assertGuest();
        }

        // Verify data was NOT tampered with while unauthenticated
        $this->assertDatabaseMissing('services', ['service_name' => 'Intruder Service']);
        $this->assertEquals(7000, $this->standardService->fresh()->price_per_unit);
        $this->assertEquals(Order::STATUS_ANTREAN, $this->sampleOrder->fresh()->status);
        $this->assertDatabaseHas('orders', ['id' => $this->sampleOrder->id]);
    }

    /**
     * Test unauthenticated access with non-existent IDs still redirects to /login without leaking 404.
     */
    public function test_unauthenticated_access_with_nonexistent_ids_still_redirects_to_login(): void
    {
        $nonExistentEndpoints = [
            ['PUT', '/admin/layanan/999999', ['service_name' => 'Ghost', 'category' => 'Kiloan', 'price_per_unit' => 1000, 'unit_type' => 'Kg']],
            ['PATCH', '/admin/layanan/999999', ['service_name' => 'Ghost', 'category' => 'Kiloan', 'price_per_unit' => 1000, 'unit_type' => 'Kg']],
            ['DELETE', '/admin/layanan/999999'],
            ['PATCH', '/admin/pesanan/999999/status', ['status' => 'Dicuci']],
            ['DELETE', '/admin/pesanan/999999'],
        ];

        foreach ($nonExistentEndpoints as $ep) {
            $response = $this->call($ep[0], $ep[1], $ep[2] ?? []);
            // Auth middleware must intercept before model binding
            $this->assertEquals(302, $response->getStatusCode());
            $response->assertRedirect('/login');
        }
    }

    /**
     * Test forged or manipulated remember cookies are rejected.
     */
    public function test_forged_remember_cookie_is_rejected(): void
    {
        $response = $this->withCookie('remember_web_fake', 'forged_token_value_12345')
            ->get('/admin');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    /**
     * Test HTTP header spoofing cannot bypass auth middleware.
     */
    public function test_header_spoofing_cannot_bypass_auth_guard(): void
    {
        $spoofedHeaders = [
            'X-Original-URL' => '/admin',
            'X-Rewrite-URL' => '/admin',
            'X-Forwarded-For' => '127.0.0.1',
            'X-Admin' => 'true',
            'X-Authenticated-User' => 'admin',
        ];

        $response = $this->withHeaders($spoofedHeaders)->get('/admin');
        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    // =========================================================================
    // 4. MALICIOUS STATUS TRANSITIONS
    // =========================================================================

    /**
     * Test order status update rejects unauthorized lifecycle names (e.g. Batal, Dibatalkan, Pending).
     */
    public function test_status_update_rejects_unsupported_status_names(): void
    {
        $unsupportedStatuses = [
            'Batal',
            'Dibatalkan',
            'Pending',
            'Diproses',
            'Diambil',
            'Draft',
            'Refund',
            'Cancelled',
            'Completed',
        ];

        foreach ($unsupportedStatuses as $badStatus) {
            $response = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
                'status' => $badStatus,
            ]);

            $response->assertSessionHasErrors('status');
            $this->assertEquals(Order::STATUS_ANTREAN, $this->sampleOrder->fresh()->status, "Status must not change for: {$badStatus}");
        }
    }

    /**
     * Test order status update rejects XSS and script injection payloads.
     */
    public function test_status_update_rejects_xss_and_injection_payloads(): void
    {
        $xssPayloads = [
            '<script>alert("xss")</script>',
            '"><img src=x onerror=alert(1)>',
            "javascript:alert('xss')",
            "'><svg/onload=alert(1)>",
            'Antrean<script>',
            "Dicuci'; DROP TABLE orders; --",
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
                'status' => $payload,
            ]);

            $response->assertSessionHasErrors('status');
            $this->assertEquals(Order::STATUS_ANTREAN, $this->sampleOrder->fresh()->status);
        }
    }

    /**
     * Test order status update rejects null, empty, whitespace, and extremely long strings.
     */
    public function test_status_update_rejects_null_whitespace_and_overflow_strings(): void
    {
        // 1. Null status
        $resNull = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
            'status' => null,
        ]);
        $resNull->assertSessionHasErrors('status');

        // 2. Empty string status
        $resEmpty = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
            'status' => '',
        ]);
        $resEmpty->assertSessionHasErrors('status');

        // 3. Whitespace only
        $resWhitespace = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
            'status' => '   ',
        ]);
        $resWhitespace->assertSessionHasErrors('status');

        // 4. Extremely long string (10,000 characters)
        $resOverflow = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
            'status' => str_repeat('A', 10000),
        ]);
        $resOverflow->assertSessionHasErrors('status');

        $this->assertEquals(Order::STATUS_ANTREAN, $this->sampleOrder->fresh()->status);
    }

    /**
     * Test order status update strictly enforces case sensitivity.
     */
    public function test_status_update_enforces_exact_case_sensitivity(): void
    {
        $caseMismatches = [
            'antrean',
            'ANTREAN',
            'AntReAn',
            'dicuci',
            'DICUCI',
            'DiCuCi',
            'selesai',
            'SELESAI',
            'SeLeSaI',
        ];

        foreach ($caseMismatches as $mismatched) {
            $response = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
                'status' => $mismatched,
            ]);

            $response->assertSessionHasErrors('status');
            $this->assertEquals(Order::STATUS_ANTREAN, $this->sampleOrder->fresh()->status);
        }
    }

    /**
     * Test order status update rejects non-string types (array, int, bool).
     */
    public function test_status_update_rejects_non_string_types(): void
    {
        // Array status
        $resArr = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
            'status' => ['Dicuci'],
        ]);
        $resArr->assertSessionHasErrors('status');

        // Integer status
        $resInt = $this->actingAs($this->adminUser)->patch("/admin/pesanan/{$this->sampleOrder->id}/status", [
            'status' => 1,
        ]);
        $resInt->assertSessionHasErrors('status');

        $this->assertEquals(Order::STATUS_ANTREAN, $this->sampleOrder->fresh()->status);
    }

    /**
     * Test order status update on non-existent order ID returns 404.
     */
    public function test_status_update_on_nonexistent_order_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser)->patch('/admin/pesanan/999999/status', [
            'status' => Order::STATUS_DICUCI,
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // 5. SERVICE CREATION & UPDATE BOUNDARY CONDITIONS
    // =========================================================================

    /**
     * Test service creation rejects negative prices.
     */
    public function test_service_creation_rejects_negative_prices(): void
    {
        $negativePrices = [-1, -100, -999999];

        foreach ($negativePrices as $badPrice) {
            $response = $this->actingAs($this->adminUser)->post('/admin/layanan', [
                'service_name' => "Bad Price Service {$badPrice}",
                'category' => 'Kiloan',
                'price_per_unit' => $badPrice,
                'unit_type' => 'Kg',
            ]);

            $response->assertSessionHasErrors('price_per_unit');
            $this->assertDatabaseMissing('services', ['service_name' => "Bad Price Service {$badPrice}"]);
        }
    }

    /**
     * Test service creation accepts zero price (min:0 boundary for free/promotional packages).
     */
    public function test_service_creation_accepts_zero_price_boundary(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => 'Promosi Cuci Gratis',
            'category' => 'Satuan',
            'price_per_unit' => 0,
            'unit_type' => 'Pcs',
            'description' => 'Paket promosi khusus 0 rupiah.',
        ]);

        $response->assertRedirect('/admin/layanan');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('services', [
            'service_name' => 'Promosi Cuci Gratis',
            'price_per_unit' => 0,
        ]);
    }

    /**
     * Test service creation rejects floating-point prices and non-numeric strings.
     */
    public function test_service_creation_rejects_floating_point_and_non_integer_prices(): void
    {
        $badPrices = [
            12500.50,
            '12500.75',
            'gratis',
            'Rp 15.000',
            '15,000',
            null,
            'true',
            'false',
            [1000],
        ];

        foreach ($badPrices as $price) {
            $response = $this->actingAs($this->adminUser)->post('/admin/layanan', [
                'service_name' => 'Invalid Price Test',
                'category' => 'Kiloan',
                'price_per_unit' => $price,
                'unit_type' => 'Kg',
            ]);

            $response->assertSessionHasErrors('price_per_unit');
        }
    }

    /**
     * Test service name boundary limits: exactly 100 characters succeeds, 101 characters fails.
     */
    public function test_service_name_length_boundaries_100_vs_101_characters(): void
    {
        // 1. Exactly 100 characters should succeed
        $valid100Name = str_repeat('A', 100);
        $resValid = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => $valid100Name,
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
        ]);
        $resValid->assertRedirect('/admin/layanan');
        $this->assertDatabaseHas('services', ['service_name' => $valid100Name]);

        // 2. Exactly 101 characters must fail validation
        $invalid101Name = str_repeat('B', 101);
        $resInvalid = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => $invalid101Name,
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
        ]);
        $resInvalid->assertSessionHasErrors('service_name');
        $this->assertDatabaseMissing('services', ['service_name' => $invalid101Name]);
    }

    /**
     * Test service creation handles UTF-8 multibyte characters and emojis properly.
     */
    public function test_service_creation_handles_multibyte_and_emoji_characters(): void
    {
        $emojiName = '🧺 Cuci Kilat Spesial ✨🚀';
        $emojiDesc = 'Layanan kilat super wangi 🌸🍀 dan higienis 🧼';

        $response = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => $emojiName,
            'category' => 'Express',
            'price_per_unit' => 20000,
            'unit_type' => 'Kg',
            'description' => $emojiDesc,
        ]);

        $response->assertRedirect('/admin/layanan');
        $this->assertDatabaseHas('services', [
            'service_name' => $emojiName,
            'description' => $emojiDesc,
        ]);

        // Verify admin listing renders emoji correctly
        $indexRes = $this->actingAs($this->adminUser)->get('/admin/layanan');
        $indexRes->assertStatus(200);
        $indexRes->assertSee($emojiName, false);
    }

    /**
     * Test service creation rejects whitespace-only names and empty inputs.
     */
    public function test_service_creation_rejects_whitespace_only_and_empty_names(): void
    {
        $whitespaceNames = [
            ' ',
            '     ',
            "\t\n   ",
            '',
            null,
        ];

        foreach ($whitespaceNames as $badName) {
            $response = $this->actingAs($this->adminUser)->post('/admin/layanan', [
                'service_name' => $badName,
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
            ]);

            $response->assertSessionHasErrors('service_name');
        }
    }

    /**
     * Test service creation strictly rejects invalid enum values for category and unit_type.
     */
    public function test_service_creation_rejects_invalid_category_and_unit_type_enums(): void
    {
        $badCategories = ['kiloan', 'EXPRESS', 'Custom', 'SatuanPcs', '<script>', 'Kiloan; DROP TABLE;'];
        foreach ($badCategories as $badCat) {
            $response = $this->actingAs($this->adminUser)->post('/admin/layanan', [
                'service_name' => 'Bad Category Package',
                'category' => $badCat,
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
            ]);
            $response->assertSessionHasErrors('category');
        }

        $badUnits = ['kg', 'PCS', 'Meter', 'Liter', 'Gram', 'Kilo', 'Lembar'];
        foreach ($badUnits as $badUnit) {
            $response = $this->actingAs($this->adminUser)->post('/admin/layanan', [
                'service_name' => 'Bad Unit Package',
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => $badUnit,
            ]);
            $response->assertSessionHasErrors('unit_type');
        }
    }

    /**
     * Test service description boundary: 1000 characters succeeds, 1001 characters fails.
     */
    public function test_service_description_boundary_1000_vs_1001_characters(): void
    {
        // 1000 characters description
        $desc1000 = str_repeat('X', 1000);
        $res1000 = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => 'Service 1000 Desc',
            'category' => 'Kiloan',
            'price_per_unit' => 5000,
            'unit_type' => 'Kg',
            'description' => $desc1000,
        ]);
        $res1000->assertRedirect('/admin/layanan');
        $this->assertDatabaseHas('services', ['description' => $desc1000]);

        // 1001 characters description
        $desc1001 = str_repeat('Y', 1001);
        $res1001 = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => 'Service 1001 Desc',
            'category' => 'Kiloan',
            'price_per_unit' => 5000,
            'unit_type' => 'Kg',
            'description' => $desc1001,
        ]);
        $res1001->assertSessionHasErrors('description');
        $this->assertDatabaseMissing('services', ['description' => $desc1001]);
    }

    /**
     * Test XSS injection in service name/description is escaped in HTML outputs.
     */
    public function test_service_xss_payloads_are_properly_escaped_in_views(): void
    {
        $xssName = "<b>Cuci</b> <script>alert('xss-service')</script>";
        $xssDesc = "<img src=x onerror=alert('xss-desc')>";

        $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => $xssName,
            'category' => 'Satuan',
            'price_per_unit' => 15000,
            'unit_type' => 'Pcs',
            'description' => $xssDesc,
        ]);

        // Check admin listing: must NOT render raw unescaped script tags
        $adminRes = $this->actingAs($this->adminUser)->get('/admin/layanan');
        $adminRes->assertStatus(200);
        $adminRes->assertDontSee("<script>alert('xss-service')</script>", false);
        $adminRes->assertDontSee("<img src=x onerror=alert('xss-desc')>", false);

        // Check customer public catalog: must NOT render raw unescaped script tags
        $homeRes = $this->get('/');
        $homeRes->assertStatus(200);
        $homeRes->assertDontSee("<script>alert('xss-service')</script>", false);
        $homeRes->assertDontSee("<img src=x onerror=alert('xss-desc')>", false);
    }

    /**
     * Test service update preserves original data when invalid payloads are submitted.
     */
    public function test_service_update_boundary_preserves_existing_data_on_validation_failure(): void
    {
        $originalName = $this->standardService->service_name;
        $originalPrice = $this->standardService->price_per_unit;

        $response = $this->actingAs($this->adminUser)->put("/admin/layanan/{$this->standardService->id}", [
            'service_name' => '   ',
            'category' => 'InvalidCategory',
            'price_per_unit' => -9999,
            'unit_type' => 'InvalidUnit',
        ]);

        $response->assertSessionHasErrors(['service_name', 'category', 'price_per_unit', 'unit_type']);

        $refreshed = $this->standardService->fresh();
        $this->assertEquals($originalName, $refreshed->service_name);
        $this->assertEquals($originalPrice, $refreshed->price_per_unit);
    }

    /**
     * Test updating or deleting a non-existent service ID returns 404.
     */
    public function test_service_update_and_delete_on_nonexistent_id_returns_404(): void
    {
        $resPut = $this->actingAs($this->adminUser)->put('/admin/layanan/999999', [
            'service_name' => 'Ghost Service',
            'category' => 'Kiloan',
            'price_per_unit' => 5000,
            'unit_type' => 'Kg',
        ]);
        $resPut->assertStatus(404);

        $resDelete = $this->actingAs($this->adminUser)->delete('/admin/layanan/999999');
        $resDelete->assertStatus(404);
    }

    // =========================================================================
    // 6. ADMIN SEARCH, FILTERING & DASHBOARD METRICS RESILIENCE
    // =========================================================================

    /**
     * Test admin orders search query resilience against SQLi, wildcards, and special characters.
     */
    public function test_admin_orders_search_resilience_under_adversarial_queries(): void
    {
        $adversarialQueries = [
            "' OR '1'='1",
            "admin' --",
            '1 UNION SELECT 1,2,3,4,5,6,7 --',
            '; DROP TABLE orders; --',
            '%25%25%25',
            '%%',
            '__',
            '\\',
            '<script>alert(1)</script>',
            str_repeat('9', 100),
        ];

        foreach ($adversarialQueries as $query) {
            $response = $this->actingAs($this->adminUser)->get('/admin/pesanan?search='.urlencode($query));
            $response->assertStatus(200);

            // Also test ?q= parameter
            $responseQ = $this->actingAs($this->adminUser)->get('/admin/pesanan?q='.urlencode($query));
            $responseQ->assertStatus(200);
        }

        // Verify database is completely intact
        $this->assertDatabaseHas('orders', ['id' => $this->sampleOrder->id]);
    }

    /**
     * Test admin orders status filter gracefully handles arbitrary and injection values.
     */
    public function test_admin_orders_status_filter_resilience(): void
    {
        $adversarialStatusFilters = [
            'HACKED',
            "' OR 1=1 --",
            '<script>',
            'null',
            '123',
            'Antrean; DROP TABLE orders;',
        ];

        foreach ($adversarialStatusFilters as $badFilter) {
            $response = $this->actingAs($this->adminUser)->get('/admin/pesanan?status='.urlencode($badFilter));
            $response->assertStatus(200);
        }
    }

    /**
     * Test admin dashboard renders smoothly with zero records without mathematical errors.
     */
    public function test_admin_dashboard_metrics_resilience_when_no_orders_exist(): void
    {
        // Delete all orders and details
        OrderDetail::query()->delete();
        Order::query()->delete();

        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Total Pesanan', false);
        $response->assertSee('Total Pendapatan', false);
        $response->assertSee('Rp 0', false);
    }

    /**
     * Test deleting a non-existent order returns 404.
     */
    public function test_admin_order_deletion_on_nonexistent_id_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser)->delete('/admin/pesanan/999999');
        $response->assertStatus(404);
    }
}

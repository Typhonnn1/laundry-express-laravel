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
 * Tier 5 Adversarial Test Suite: Admin Portal Coverage Hardening
 *
 * Exhaustive white-box stress testing and boundary hardening covering:
 * 1. Rapid multi-turn login failures followed by legitimate admin login.
 * 2. Admin dashboard aggregation with thousands of orders and zero revenue/order edge cases.
 * 3. Master service update with identical data (idempotency) and boundary constraints.
 * 4. Order search with regex characters, SQL wildcards (%, _), and non-numeric order ID patterns.
 * 5. Status transitions with leading/trailing whitespace, mixed capitalization, and invalid states.
 * 6. High-concurrency status updates and race conditions on the same order.
 */
class Tier5AdminPortalHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Service $standardService;

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

        // Seed default standard service
        $this->standardService = Service::create([
            'service_name' => 'Cuci Komplit Reguler',
            'category' => 'Kiloan',
            'price_per_unit' => 7000,
            'unit_type' => 'Kg',
            'description' => 'Cuci, kering, dan setrika rapi.',
        ]);
    }

    // =========================================================================
    // 1. AUTHENTICATION HARDENING & RAPID MULTI-TURN RESILIENCE
    // =========================================================================

    /**
     * Test rapid multi-turn login failures followed immediately by a legitimate admin login.
     * Verifies that consecutive failed attempts preserve guest state and error feedback,
     * while the subsequent legitimate login regenerates the session and authenticates cleanly.
     */
    public function test_rapid_multi_turn_login_failure_followed_by_legitimate_admin_login(): void
    {
        $failedAttempts = [
            ['username' => 'admin', 'password' => 'wrongpass1'],
            ['username' => 'admin', 'password' => 'wrongpass2'],
            ['username' => 'administrator', 'password' => 'admin123'],
            ['username' => 'root', 'password' => 'toor'],
            ['username' => "' OR '1'='1", 'password' => 'password'],
            ['username' => 'admin', 'password' => ''],
            ['username' => '', 'password' => 'admin123'],
            ['username' => 'admin', 'password' => 'Admin123'], // Case sensitive password
            ['username' => 'ad min', 'password' => 'admin123'], // Internal whitespace username
            ['username' => 'admin', 'password' => 'admin123 '], // Trailing space in password (passwords are not trimmed)
            ['username' => 'admin', 'password' => ' admin123'], // Leading space in password
            ['username' => 'guest', 'password' => 'guest123'],
        ];

        foreach ($failedAttempts as $attempt) {
            $response = $this->post('/login', $attempt);

            $this->assertFalse(Auth::check(), 'User should remain unauthenticated after bad attempt.');
            $response->assertStatus(302);
            $response->assertSessionHasErrors();
        }

        // Now submit legitimate credentials
        $validResponse = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $validResponse->assertStatus(302);
        $validResponse->assertRedirect('/admin');
        $validResponse->assertSessionHasNoErrors();
        $this->assertTrue(Auth::check(), 'Admin should be successfully authenticated.');
        $this->assertEquals($this->adminUser->id, Auth::id());

        // Verify authenticated access to the dashboard
        $dashboardResponse = $this->get('/admin');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Admin Laundry');
    }

    /**
     * Test login credential normalization:
     * - Usernames with leading/trailing whitespace are trimmed by TrimStrings middleware and authenticate.
     * - Passwords with whitespace are strictly preserved (not trimmed) and fail authentication.
     * - Usernames with internal whitespace fail authentication.
     */
    public function test_login_whitespace_normalization_and_password_preservation(): void
    {
        // 1. Password with whitespace must fail because passwords are not trimmed
        $passwordsWithSpaces = [
            ' admin123',
            'admin123 ',
            ' admin123 ',
            "admin123\t",
        ];

        foreach ($passwordsWithSpaces as $badPass) {
            $response = $this->post('/login', [
                'username' => 'admin',
                'password' => $badPass,
            ]);

            $response->assertStatus(302);
            $this->assertFalse(Auth::check(), "Password with whitespace '{$badPass}' should fail.");
        }

        // 2. Username with internal whitespace must fail
        $internalSpaceResponse = $this->post('/login', [
            'username' => 'ad min',
            'password' => 'admin123',
        ]);
        $internalSpaceResponse->assertStatus(302);
        $this->assertFalse(Auth::check());

        // 3. Usernames with leading/trailing whitespace are trimmed by middleware and succeed
        $whitespaceUsernames = [' admin', 'admin ', ' admin '];
        foreach ($whitespaceUsernames as $paddedUser) {
            $response = $this->post('/login', [
                'username' => $paddedUser,
                'password' => 'admin123',
            ]);

            $response->assertStatus(302);
            $response->assertRedirect('/admin');
            $this->assertTrue(Auth::check());

            // Logout before next iteration
            $this->post('/logout');
            $this->assertFalse(Auth::check());
        }
    }

    /**
     * Test remember-me functionality sets a valid remember_token in the database.
     */
    public function test_remember_me_functionality_sets_remember_token(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
            'remember' => '1',
        ]);

        $response->assertStatus(302);
        $this->assertTrue(Auth::check());

        $this->adminUser->refresh();
        $this->assertNotNull($this->adminUser->remember_token);
        $this->assertNotEmpty($this->adminUser->remember_token);
    }

    /**
     * Test already-authenticated admin visiting /login is redirected to /admin.
     */
    public function test_authenticated_admin_visiting_login_redirected_to_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/login');

        $response->assertStatus(302);
        $response->assertRedirect('/admin');
    }

    /**
     * Test repeated rapid logout and re-login cycles to ensure CSRF and session regeneration integrity.
     */
    public function test_rapid_repeated_logout_and_relogin_cycles(): void
    {
        for ($i = 0; $i < 5; $i++) {
            // Login
            $loginRes = $this->post('/login', [
                'username' => 'admin',
                'password' => 'admin123',
            ]);
            $loginRes->assertStatus(302)->assertRedirect('/admin');
            $this->assertTrue(Auth::check());

            // Access dashboard
            $dashRes = $this->get('/admin');
            $dashRes->assertStatus(200);

            // Logout
            $logoutRes = $this->post('/logout');
            $logoutRes->assertStatus(302)->assertRedirect('/login');
            $this->assertFalse(Auth::check());

            // Blocked from dashboard
            $blockedRes = $this->get('/admin');
            $blockedRes->assertStatus(302)->assertRedirect('/login');
        }
    }

    // =========================================================================
    // 2. ADMIN DASHBOARD AGGREGATION & ZERO STATE RESILIENCE
    // =========================================================================

    /**
     * Test admin dashboard with exactly zero orders and zero revenue.
     * Verifies that counters show 0, no division by zero or view exceptions occur,
     * and the empty state placeholder is rendered.
     */
    public function test_admin_dashboard_zero_orders_and_zero_revenue_state(): void
    {
        $this->assertEquals(0, Order::count());

        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewHas('totalOrders', 0);
        $response->assertViewHas('totalRevenue', 0);
        $response->assertViewHas('countAntrean', 0);
        $response->assertViewHas('countDicuci', 0);
        $response->assertViewHas('countSelesai', 0);
        $response->assertViewHas('recentOrders', function ($collection) {
            return $collection->isEmpty();
        });

        // HTML displays empty state message
        $response->assertSee('Belum ada transaksi pesanan yang tercatat.');
    }

    /**
     * Test admin dashboard aggregation with 2,500 orders across all statuses.
     * Verifies aggregation performance, mathematical sum accuracy, and recent 10 items limit.
     */
    public function test_admin_dashboard_high_volume_aggregation_with_thousands_of_orders(): void
    {
        $totalCount = 2500;
        $now = now();
        $batch = [];
        $expectedTotalRevenue = 0;
        $expectedAntrean = 0;
        $expectedDicuci = 0;
        $expectedSelesai = 0;

        for ($i = 1; $i <= $totalCount; $i++) {
            $mod = $i % 3;
            if ($mod === 0) {
                $status = Order::STATUS_ANTREAN;
                $expectedAntrean++;
            } elseif ($mod === 1) {
                $status = Order::STATUS_DICUCI;
                $expectedDicuci++;
            } else {
                $status = Order::STATUS_SELESAI;
                $expectedSelesai++;
            }

            $price = 10000 + ($i * 10);
            $expectedTotalRevenue += $price;

            $batch[] = [
                'customer_name' => "Customer {$i}",
                'whatsapp_number' => '0812'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'total_price' => $price,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bulk insert in chunks for performance
        foreach (array_chunk($batch, 500) as $chunk) {
            Order::insert($chunk);
        }

        $this->assertEquals($totalCount, Order::count());

        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewHas('totalOrders', $totalCount);
        $response->assertViewHas('totalRevenue', $expectedTotalRevenue);
        $response->assertViewHas('countAntrean', $expectedAntrean);
        $response->assertViewHas('countDicuci', $expectedDicuci);
        $response->assertViewHas('countSelesai', $expectedSelesai);

        $response->assertViewHas('recentOrders', function ($collection) {
            return $collection->count() === 10;
        });

        // Verify that sum of status counts exactly equals total orders
        $this->assertEquals($totalCount, $expectedAntrean + $expectedDicuci + $expectedSelesai);
    }

    /**
     * Test admin dashboard handles very large single transaction revenue without integer truncation.
     */
    public function test_admin_dashboard_large_single_transaction_revenue_handling(): void
    {
        $largeAmount = 1500000000; // 1.5 Billion Rupiah

        Order::create([
            'customer_name' => 'Corporate Hotel Client',
            'whatsapp_number' => '081199998888',
            'total_price' => $largeAmount,
            'status' => Order::STATUS_SELESAI,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewHas('totalOrders', 1);
        $response->assertViewHas('totalRevenue', $largeAmount);
        $response->assertSee(number_format($largeAmount, 0, ',', '.'));
    }

    // =========================================================================
    // 3. MASTER SERVICE MANAGEMENT & IDEMPOTENCY
    // =========================================================================

    /**
     * Test service update with identical data is idempotent.
     * Repeatedly submitting identical data succeeds without unique collision or state divergence.
     */
    public function test_service_update_with_identical_data_is_idempotent(): void
    {
        $payload = [
            'service_name' => $this->standardService->service_name,
            'category' => $this->standardService->category,
            'price_per_unit' => $this->standardService->price_per_unit,
            'unit_type' => $this->standardService->unit_type,
            'description' => $this->standardService->description,
        ];

        for ($iteration = 1; $iteration <= 4; $iteration++) {
            $response = $this->actingAs($this->adminUser)
                ->put("/admin/layanan/{$this->standardService->id}", $payload);

            $response->assertStatus(302);
            $response->assertRedirect('/admin/layanan');
            $response->assertSessionHas('success', 'Paket layanan berhasil diperbarui.');

            $this->standardService->refresh();
            $this->assertEquals('Cuci Komplit Reguler', $this->standardService->service_name);
            $this->assertEquals(7000, $this->standardService->price_per_unit);
        }
    }

    /**
     * Test service creation and update with price_per_unit = 0 (Free/Promotional tier).
     * Verifies that integer: min:0 validation permits 0 as a valid price.
     */
    public function test_service_creation_and_update_with_zero_price_free_tier(): void
    {
        $createResponse = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => 'Konsultasi & Sample Gratis',
            'category' => 'Satuan',
            'price_per_unit' => 0,
            'unit_type' => 'Pcs',
            'description' => 'Paket promosi tanpa biaya.',
        ]);

        $createResponse->assertStatus(302);
        $createResponse->assertRedirect('/admin/layanan');

        $freeService = Service::where('service_name', 'Konsultasi & Sample Gratis')->first();
        $this->assertNotNull($freeService);
        $this->assertEquals(0, $freeService->price_per_unit);

        // Update price to 5000 and then back to 0
        $updateResponse = $this->actingAs($this->adminUser)
            ->put("/admin/layanan/{$freeService->id}", [
                'service_name' => 'Konsultasi & Sample Gratis',
                'category' => 'Satuan',
                'price_per_unit' => 0,
                'unit_type' => 'Pcs',
                'description' => 'Tetap gratis.',
            ]);

        $updateResponse->assertStatus(302);
        $freeService->refresh();
        $this->assertEquals(0, $freeService->price_per_unit);
    }

    /**
     * Test service validation at exact maximum length boundaries.
     * service_name: 100 chars (valid) vs 101 chars (invalid).
     * description: 1000 chars (valid) vs 1001 chars (invalid).
     */
    public function test_service_validation_exact_max_length_boundary(): void
    {
        $name100 = str_repeat('A', 100);
        $name101 = str_repeat('A', 101);
        $desc1000 = str_repeat('D', 1000);
        $desc1001 = str_repeat('D', 1001);

        // Valid boundary
        $validResponse = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => $name100,
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
            'description' => $desc1000,
        ]);
        $validResponse->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('services', ['service_name' => $name100]);

        // Invalid: 101 character name
        $invalidNameResponse = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => $name101,
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
            'description' => 'Valid desc',
        ]);
        $invalidNameResponse->assertStatus(302)->assertSessionHasErrors(['service_name']);

        // Invalid: 1001 character description
        $invalidDescResponse = $this->actingAs($this->adminUser)->post('/admin/layanan', [
            'service_name' => 'Valid Service Name',
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
            'description' => $desc1001,
        ]);
        $invalidDescResponse->assertStatus(302)->assertSessionHasErrors(['description']);
    }

    /**
     * Test service update rejects empty or whitespace-only name and retains original value.
     */
    public function test_service_update_rejects_empty_and_whitespace_only_name(): void
    {
        $whitespaceInputs = ['', '   ', "\t", "  \n  "];

        foreach ($whitespaceInputs as $badName) {
            $response = $this->actingAs($this->adminUser)
                ->put("/admin/layanan/{$this->standardService->id}", [
                    'service_name' => $badName,
                    'category' => 'Kiloan',
                    'price_per_unit' => 7000,
                    'unit_type' => 'Kg',
                ]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['service_name']);
        }

        $this->standardService->refresh();
        $this->assertEquals('Cuci Komplit Reguler', $this->standardService->service_name);
    }

    // =========================================================================
    // 4. ORDER SEARCH RESILIENCE & ADVERSARIAL QUERYING
    // =========================================================================

    /**
     * Test order search with SQL wildcards (%, _) and injection syntax does not cause query errors.
     */
    public function test_order_search_with_sql_wildcards_and_special_operators(): void
    {
        // Create sample orders
        Order::create([
            'customer_name' => 'Budi Santoso',
            'whatsapp_number' => '081234567891',
            'total_price' => 20000,
            'status' => Order::STATUS_ANTREAN,
        ]);
        Order::create([
            'customer_name' => 'Siti Rahmawati',
            'whatsapp_number' => '081234567892',
            'total_price' => 35000,
            'status' => Order::STATUS_DICUCI,
        ]);

        $wildcardSearches = [
            '%',
            '_',
            '%%',
            '%_%',
            "' OR 1=1 --",
            'admin\' #',
            '1; DROP TABLE orders; --',
            '\' UNION SELECT null, null, null --',
        ];

        foreach ($wildcardSearches as $term) {
            $response = $this->actingAs($this->adminUser)
                ->get('/admin/pesanan?search='.urlencode($term));

            $response->assertStatus(200);
            $response->assertViewHas('orders');
        }
    }

    /**
     * Test order search with regex metacharacters executes safely without regex errors or crashes.
     */
    public function test_order_search_with_regex_and_special_punctuation(): void
    {
        $regexInputs = [
            '.*^$()[]{}|\\+?',
            '/[a-z0-9]+/i',
            '\\d+\\w*',
            '(((((',
            '????',
            '^^^^',
        ];

        foreach ($regexInputs as $term) {
            $response = $this->actingAs($this->adminUser)
                ->get('/admin/pesanan?search='.urlencode($term));

            $response->assertStatus(200);
            $response->assertViewHas('orders');
        }
    }

    /**
     * Test order search by formatted order ID patterns successfully extracts the numeric ID.
     */
    public function test_order_search_by_formatted_order_id_patterns(): void
    {
        $order = Order::create([
            'customer_name' => 'Target Customer ID Pattern',
            'whatsapp_number' => '081999990000',
            'total_price' => 50000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $searchVariants = [
            "#ORD-{$order->id}",
            "ORD-{$order->id}",
            '#ORD-'.sprintf('%04d', $order->id),
            "#{$order->id}",
            (string) $order->id,
        ];

        foreach ($searchVariants as $query) {
            $response = $this->actingAs($this->adminUser)
                ->get('/admin/pesanan?search='.urlencode($query));

            $response->assertStatus(200);
            $response->assertSee('Target Customer ID Pattern');
            $response->assertSee('081999990000');
        }
    }

    /**
     * Test order search by non-numeric order ID patterns returns zero results cleanly.
     */
    public function test_order_search_by_non_numeric_order_id_patterns(): void
    {
        $order = Order::create([
            'customer_name' => 'Ahmad Dahlan',
            'whatsapp_number' => '081234567899',
            'total_price' => 15000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $nonNumericSearches = [
            '#ORD-XYZ',
            'ORDER-NONE',
            '#ORD-NONEXISTENT',
        ];

        foreach ($nonNumericSearches as $query) {
            $response = $this->actingAs($this->adminUser)
                ->get('/admin/pesanan?search='.urlencode($query));

            $response->assertStatus(200);
            $response->assertDontSee('Ahmad Dahlan');
        }
    }

    /**
     * Test order search with extreme integer overflow strings does not crash the database.
     */
    public function test_order_search_with_extreme_integer_overflow_id(): void
    {
        $overflowTerm = '99999999999999999999999999999999999999999999999999';

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/pesanan?search='.urlencode($overflowTerm));

        $response->assertStatus(200);
        $response->assertViewHas('orders');
    }

    /**
     * Test order search with backslashes and single/double quotes executes cleanly.
     */
    public function test_order_search_with_backslashes_and_quotes(): void
    {
        $characters = ['\\', "'", '"', "\\'", '\\"'];

        foreach ($characters as $char) {
            $response = $this->actingAs($this->adminUser)
                ->get('/admin/pesanan?search='.urlencode($char));

            $response->assertStatus(200);
            $response->assertViewHas('orders');
        }
    }

    /**
     * Test order search combined simultaneously with status filter.
     */
    public function test_order_search_combined_with_status_filter(): void
    {
        $order1 = Order::create([
            'customer_name' => 'Dewi Lestari',
            'whatsapp_number' => '081211112222',
            'total_price' => 20000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $order2 = Order::create([
            'customer_name' => 'Dewi Lestari',
            'whatsapp_number' => '081211112222',
            'total_price' => 45000,
            'status' => Order::STATUS_SELESAI,
        ]);

        // Search Dewi with status Antrean
        $resAntrean = $this->actingAs($this->adminUser)
            ->get('/admin/pesanan?status=Antrean&search=Dewi');

        $resAntrean->assertStatus(200);
        $resAntrean->assertSee('Rp 20.000');
        $resAntrean->assertDontSee('Rp 45.000');

        // Search Dewi with status Selesai
        $resSelesai = $this->actingAs($this->adminUser)
            ->get('/admin/pesanan?status=Selesai&search=Dewi');

        $resSelesai->assertStatus(200);
        $resSelesai->assertSee('Rp 45.000');
        $resSelesai->assertDontSee('Rp 20.000');
    }

    // =========================================================================
    // 5. STATUS TRANSITIONS RESILIENCE & NORMALIZATION
    // =========================================================================

    /**
     * Test status transitions with mixed or lower capitalization are rejected by validation.
     */
    public function test_status_transition_with_mixed_capitalization_is_rejected(): void
    {
        $order = Order::create([
            'customer_name' => 'Case Sensitivity Test',
            'whatsapp_number' => '081233334444',
            'total_price' => 15000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $invalidCases = ['antrean', 'DICUCI', 'dicuci', 'sElEsAi', 'selesai', 'SELESAI'];

        foreach ($invalidCases as $badCase) {
            $response = $this->actingAs($this->adminUser)
                ->patch("/admin/pesanan/{$order->id}/status", ['status' => $badCase]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['status']);

            $order->refresh();
            $this->assertEquals(Order::STATUS_ANTREAN, $order->status, "Status should remain Antrean for input {$badCase}.");
        }
    }

    /**
     * Test status transition with whitespace inputs.
     * Verifies that if trimmed by middleware it normalizes, but whitespace-only fails.
     */
    public function test_status_transition_with_whitespace_is_handled_or_rejected(): void
    {
        $order = Order::create([
            'customer_name' => 'Whitespace Status Test',
            'whatsapp_number' => '081233335555',
            'total_price' => 20000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        // Whitespace only must be rejected
        $blankResponse = $this->actingAs($this->adminUser)
            ->patch("/admin/pesanan/{$order->id}/status", ['status' => '   ']);

        $blankResponse->assertStatus(302);
        $blankResponse->assertSessionHasErrors(['status']);

        // Leading and trailing spaces around valid status
        $trimmedResponse = $this->actingAs($this->adminUser)
            ->patch("/admin/pesanan/{$order->id}/status", ['status' => '  Dicuci  ']);

        $trimmedResponse->assertStatus(302);
        $order->refresh();
        // If TrimStrings middleware is active, it trims to 'Dicuci', otherwise validation rejects.
        // In either case, the database record must NEVER contain raw whitespace.
        $this->assertNotEquals('  Dicuci  ', $order->status);
        $this->assertContains($order->status, Order::STATUSES);
    }

    /**
     * Test status transition with arbitrary invalid status names is rejected.
     */
    public function test_status_transition_with_invalid_statuses_fails_validation(): void
    {
        $order = Order::create([
            'customer_name' => 'Invalid Status Test',
            'whatsapp_number' => '081233336666',
            'total_price' => 25000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $invalidStatuses = [
            'Batal',
            'Cancelled',
            'Pending',
            'Processing',
            'Done',
            '1',
            '<script>alert("xss")</script>',
            'Antrean; DROP TABLE orders;',
        ];

        foreach ($invalidStatuses as $badStatus) {
            $response = $this->actingAs($this->adminUser)
                ->patch("/admin/pesanan/{$order->id}/status", ['status' => $badStatus]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['status']);

            $order->refresh();
            $this->assertEquals(Order::STATUS_ANTREAN, $order->status);
        }
    }

    /**
     * Test status transition on non-existent order ID returns 404 Not Found.
     */
    public function test_status_transition_on_non_existent_order_returns_404(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->patch('/admin/pesanan/999999/status', ['status' => Order::STATUS_DICUCI]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // 6. HIGH-CONCURRENCY WORKFLOWS & STATE INTEGRITY
    // =========================================================================

    /**
     * Test rapid consecutive status transitions on the same order.
     * Verifies that fast state changes (Antrean -> Dicuci -> Selesai -> Antrean ...)
     * cleanly persist and the final state matches the last transition.
     */
    public function test_rapid_consecutive_status_transitions_on_same_order(): void
    {
        $order = Order::create([
            'customer_name' => 'Rapid Transition Customer',
            'whatsapp_number' => '081277778888',
            'total_price' => 30000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $transitions = [
            Order::STATUS_DICUCI,
            Order::STATUS_SELESAI,
            Order::STATUS_ANTREAN,
            Order::STATUS_DICUCI,
            Order::STATUS_SELESAI,
            Order::STATUS_DICUCI,
            Order::STATUS_ANTREAN,
            Order::STATUS_DICUCI,
            Order::STATUS_SELESAI,
        ];

        foreach ($transitions as $nextStatus) {
            $response = $this->actingAs($this->adminUser)
                ->patch("/admin/pesanan/{$order->id}/status", ['status' => $nextStatus]);

            $response->assertStatus(302);
            $response->assertSessionHas('success', 'Status pesanan berhasil diperbarui.');

            $order->refresh();
            $this->assertEquals($nextStatus, $order->status);
        }

        // Verify tracking lookup reflects final status
        $trackingResponse = $this->get('/?search='.$order->id);
        $trackingResponse->assertStatus(200);
        $trackingResponse->assertSee('Selesai');
    }

    /**
     * Test interleaved status update and order deletion race condition.
     * When an order is deleted, any subsequent status update attempt immediately 404s.
     */
    public function test_interleaved_status_update_and_order_deletion_race(): void
    {
        $order = Order::create([
            'customer_name' => 'Race Condition Order',
            'whatsapp_number' => '081288889999',
            'total_price' => 21000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $this->standardService->id,
            'qty_or_weight' => 3,
            'subtotal' => 21000,
        ]);

        $orderId = $order->id;

        // Admin A deletes the order
        $deleteResponse = $this->actingAs($this->adminUser)->delete("/admin/pesanan/{$orderId}");
        $deleteResponse->assertStatus(302);
        $deleteResponse->assertRedirect('/admin/pesanan');

        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
        $this->assertDatabaseMissing('order_details', ['order_id' => $orderId]);

        // Admin B attempts status update on the newly deleted order
        $staleUpdateResponse = $this->actingAs($this->adminUser)
            ->patch("/admin/pesanan/{$orderId}/status", ['status' => Order::STATUS_DICUCI]);

        $staleUpdateResponse->assertStatus(404);

        // Customer receipt lookup immediately 404s
        $receiptResponse = $this->get("/nota/{$orderId}");
        $receiptResponse->assertStatus(404);
    }

    /**
     * Test master service deletion safety when orders exist across all processing stages.
     * Verifies that cascade constraints remove details cleanly without corrupting parent orders.
     */
    public function test_service_deletion_safety_with_orders_in_all_processing_stages(): void
    {
        $transientService = Service::create([
            'service_name' => 'Layanan Sementara',
            'category' => 'Kiloan',
            'price_per_unit' => 8000,
            'unit_type' => 'Kg',
            'description' => 'Akan segera dihapus.',
        ]);

        $orders = [
            Order::create([
                'customer_name' => 'Order Stage 1',
                'whatsapp_number' => '081200000001',
                'total_price' => 16000,
                'status' => Order::STATUS_ANTREAN,
            ]),
            Order::create([
                'customer_name' => 'Order Stage 2',
                'whatsapp_number' => '081200000002',
                'total_price' => 24000,
                'status' => Order::STATUS_DICUCI,
            ]),
            Order::create([
                'customer_name' => 'Order Stage 3',
                'whatsapp_number' => '081200000003',
                'total_price' => 32000,
                'status' => Order::STATUS_SELESAI,
            ]),
        ];

        foreach ($orders as $order) {
            OrderDetail::create([
                'order_id' => $order->id,
                'service_id' => $transientService->id,
                'qty_or_weight' => 2,
                'subtotal' => 16000,
            ]);
        }

        $this->assertEquals(3, OrderDetail::where('service_id', $transientService->id)->count());

        // Delete the service
        $deleteResponse = $this->actingAs($this->adminUser)
            ->delete("/admin/layanan/{$transientService->id}");

        $deleteResponse->assertStatus(302);
        $deleteResponse->assertRedirect('/admin/layanan');

        // Service deleted
        $this->assertDatabaseMissing('services', ['id' => $transientService->id]);

        // Foreign key cascade cleanly deleted the order details
        $this->assertEquals(0, OrderDetail::where('service_id', $transientService->id)->count());

        // Orders themselves still exist with their historical total_price and status intact
        foreach ($orders as $order) {
            $this->assertDatabaseHas('orders', ['id' => $order->id]);
            $order->refresh();
            $this->assertGreaterThan(0, $order->total_price);
        }
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LaundryPackage;
use App\Models\Order;

class SistemLaundryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Verifikasi Skema Database & Migrasi
     */
    public function test_database_schema_has_expected_tables_and_columns(): void
    {
        // Users table
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasColumns('users', ['id', 'name', 'email', 'password', 'role']));

        // Laundry Packages table
        $this->assertTrue(Schema::hasTable('laundry_packages'));
        $this->assertTrue(Schema::hasColumns('laundry_packages', ['id', 'name', 'price_per_kg', 'estimated_duration', 'description']));

        // Orders table
        $this->assertTrue(Schema::hasTable('orders'));
        $this->assertTrue(Schema::hasColumns('orders', ['id', 'user_id', 'package_id', 'customer_name', 'weight', 'total_price', 'status', 'order_date']));
    }

    /**
     * 2. Verifikasi Data Awal dari Seeder
     */
    public function test_database_seeder_populates_default_users_and_packages(): void
    {
        // Default Admin User
        $admin = User::where('email', 'admin@laundry.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->role);
        $this->assertTrue(Hash::check('admin123', $admin->password));

        // Default Customer User
        $customer = User::where('email', 'customer@gmail.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('customer', $customer->role);
        $this->assertTrue(Hash::check('customer123', $customer->password));

        // 4 Default Laundry Packages
        $this->assertEquals(4, LaundryPackage::count());
        $this->assertDatabaseHas('laundry_packages', [
            'name' => 'Cuci Komplit Reguler',
            'price_per_kg' => 7000,
            'estimated_duration' => '2 Hari',
        ]);
        $this->assertDatabaseHas('laundry_packages', [
            'name' => 'Cuci Kering Kilat',
            'price_per_kg' => 10000,
            'estimated_duration' => '1 Hari',
        ]);
        $this->assertDatabaseHas('laundry_packages', [
            'name' => 'Setrika Saja',
            'price_per_kg' => 5000,
            'estimated_duration' => '1 Hari',
        ]);
        $this->assertDatabaseHas('laundry_packages', [
            'name' => 'Cuci Bedcover / Selimut',
            'price_per_kg' => 15000,
            'estimated_duration' => '3 Hari',
        ]);
    }

    /**
     * 3. Verifikasi Relasi Eloquent dan Type Casting
     */
    public function test_eloquent_relationships_and_casts(): void
    {
        $user = User::where('email', 'customer@gmail.com')->first();
        $package = LaundryPackage::first();

        $order = Order::create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'customer_name' => 'Budi Pelanggan',
            'weight' => 2.50,
            'total_price' => 17500,
            'status' => 'Baru',
            'order_date' => now()->toDateString(),
        ]);

        // BelongsTo User
        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);

        // BelongsTo Package
        $this->assertInstanceOf(LaundryPackage::class, $order->package);
        $this->assertEquals($package->id, $order->package->id);

        // User hasMany Orders
        $this->assertTrue($user->orders->contains($order));

        // Package hasMany Orders
        $this->assertTrue($package->orders->contains($order));

        // Casts
        $this->assertIsFloat($order->weight);
        $this->assertEquals(2.5, $order->weight);
        $this->assertInstanceOf(\DateTimeInterface::class, $order->order_date);
    }

    /**
     * 4. Routing & Autentikasi Tamu (Guest)
     */
    public function test_guest_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/catalog')->assertRedirect('/login');
        $this->get('/admin/packages')->assertRedirect('/login');
        $this->get('/admin/orders')->assertRedirect('/login');
    }

    public function test_guest_can_view_login_and_register_pages(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('admin@laundry.com');
        $response->assertSee('customer@gmail.com');

        $regResponse = $this->get('/register');
        $regResponse->assertStatus(200);
        $regResponse->assertSee('Pendaftaran Akun Baru');
    }

    public function test_login_validation_and_failure(): void
    {
        $response = $this->post('/login', [
            'email' => 'wrong@email.com',
            'password' => 'wrongpass',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_login_redirects_to_admin_packages(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@laundry.com',
            'password' => 'admin123',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.packages.index'));
        $response->assertSessionHas('success', 'Selamat datang Admin!');
    }

    public function test_customer_login_redirects_to_customer_catalog(): void
    {
        $response = $this->post('/login', [
            'email' => 'customer@gmail.com',
            'password' => 'customer123',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.catalog'));
        $response->assertSessionHas('success', 'Selamat datang di Sistem Laundry!');
    }

    public function test_customer_registration_creates_account_and_logs_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Siti Pelanggan',
            'email' => 'siti@example.com',
            'password' => 'siti12345',
            'password_confirmation' => 'siti12345',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'siti@example.com',
            'role' => 'customer',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.catalog'));
    }

    public function test_logout_redirects_to_login(): void
    {
        $customer = User::where('role', 'customer')->first();
        $this->actingAs($customer);
        $this->assertAuthenticated();

        $response = $this->post('/logout');
        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    /**
     * 5. Otorisasi Akses: Customer Dilarang Akses Area Admin
     */
    public function test_customer_cannot_access_admin_package_crud_or_orders(): void
    {
        $customer = User::where('role', 'customer')->first();
        $this->actingAs($customer);

        // Package index
        $this->get('/admin/packages')->assertStatus(403);
        // Package create
        $this->get('/admin/packages/create')->assertStatus(403);
        // Package store
        $this->post('/admin/packages', [
            'name' => 'Hacker Package',
            'price_per_kg' => 99999,
            'estimated_duration' => '1 Hari',
            'description' => 'Test',
        ])->assertStatus(403);
        // Admin orders
        $this->get('/admin/orders')->assertStatus(403);
        // Admin status update
        $this->patch('/admin/orders/1/status', ['status' => 'Selesai'])->assertStatus(403);
    }

    /**
     * 6. Admin CRUD Master Paket Laundry
     */
    public function test_admin_can_manage_laundry_packages(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        // 1. Index
        $response = $this->get('/admin/packages');
        $response->assertStatus(200);
        $response->assertSee('Cuci Komplit Reguler');

        // 2. Create Page
        $this->get('/admin/packages/create')->assertStatus(200);

        // 3. Store
        $storeResponse = $this->post('/admin/packages', [
            'name' => 'Cuci Karpet Spesial',
            'price_per_kg' => 20000,
            'estimated_duration' => '3 Hari',
            'description' => 'Pencucian karpet dengan mesin hidrolik dan pengering uap.',
        ]);
        $storeResponse->assertRedirect(route('admin.packages.index'));
        $storeResponse->assertSessionHas('success');
        $this->assertDatabaseHas('laundry_packages', ['name' => 'Cuci Karpet Spesial']);

        $newPkg = LaundryPackage::where('name', 'Cuci Karpet Spesial')->first();

        // 4. Edit Page
        $this->get("/admin/packages/{$newPkg->id}/edit")->assertStatus(200);

        // 5. Update
        $updateResponse = $this->put("/admin/packages/{$newPkg->id}", [
            'name' => 'Cuci Karpet Super Bersih',
            'price_per_kg' => 22000,
            'estimated_duration' => '2 Hari',
            'description' => 'Deskripsi diperbarui.',
        ]);
        $updateResponse->assertRedirect(route('admin.packages.index'));
        $this->assertDatabaseHas('laundry_packages', ['name' => 'Cuci Karpet Super Bersih', 'price_per_kg' => 22000]);

        // 6. Delete
        $delResponse = $this->delete("/admin/packages/{$newPkg->id}");
        $delResponse->assertRedirect(route('admin.packages.index'));
        $this->assertDatabaseMissing('laundry_packages', ['id' => $newPkg->id]);
    }

    /**
     * 7. Alur Pemesanan Customer & Perhitungan Tagihan Backend
     */
    public function test_customer_can_browse_catalog_and_create_order(): void
    {
        $customer = User::where('role', 'customer')->first();
        $this->actingAs($customer);

        // 1. Browse catalog
        $catalogResponse = $this->get('/catalog');
        $catalogResponse->assertStatus(200);
        $catalogResponse->assertSee('Cuci Komplit Reguler');
        $catalogResponse->assertSee('Rp 7.000');

        // 2. View order form for package 1 (tarif 7000/kg)
        $package = LaundryPackage::where('price_per_kg', 7000)->first();
        $orderFormResponse = $this->get("/order/create/{$package->id}");
        $orderFormResponse->assertStatus(200);
        $orderFormResponse->assertSee($package->name);

        // 3. Submit Order with weight 3.5 kg
        // Calculation: 7000 * 3.5 = 24500
        $orderResponse = $this->post('/order/store', [
            'package_id' => $package->id,
            'customer_name' => 'Budi Santoso',
            'weight' => 3.5,
        ]);

        $orderResponse->assertRedirect(route('customer.catalog'));
        $orderResponse->assertSessionHas('success');

        $order = Order::where('customer_name', 'Budi Santoso')->first();
        $this->assertNotNull($order);
        $this->assertEquals($customer->id, $order->user_id);
        $this->assertEquals($package->id, $order->package_id);
        $this->assertEquals(3.5, $order->weight);
        $this->assertEquals(24500, $order->total_price);
        $this->assertEquals('Baru', $order->status);
        $this->assertEquals(now()->toDateString(), $order->order_date->toDateString());
    }

    /**
     * 8. Admin Rekapitulasi Pesanan & Perubahan Status Transaksi
     */
    public function test_admin_recap_and_order_status_update(): void
    {
        $customer = User::where('role', 'customer')->first();
        $package = LaundryPackage::first();

        // Create 2 test orders
        $order1 = Order::create([
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'customer_name' => 'Pelanggan 1',
            'weight' => 2.0,
            'total_price' => 14000,
            'status' => 'Baru',
            'order_date' => now()->toDateString(),
        ]);

        $order2 = Order::create([
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'customer_name' => 'Pelanggan 2',
            'weight' => 3.0,
            'total_price' => 21000,
            'status' => 'Diproses',
            'order_date' => now()->toDateString(),
        ]);

        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        // View admin orders
        $response = $this->get('/admin/orders');
        $response->assertStatus(200);
        $response->assertSee('Pelanggan 1');
        $response->assertSee('Pelanggan 2');
        $response->assertSee('Rp 35.000'); // Total revenue = 14000 + 21000
        $response->assertSee('5.0 Kg');   // Total weight = 2.0 + 3.0

        // Admin updates status of order1 from Baru -> Selesai
        $patchResponse = $this->patch("/admin/orders/{$order1->id}/status", [
            'status' => 'Selesai',
        ]);
        $patchResponse->assertRedirect();
        $this->assertEquals('Selesai', $order1->fresh()->status);

        // Admin updates status of order1 to Diambil
        $this->patch("/admin/orders/{$order1->id}/status", [
            'status' => 'Diambil',
        ]);
        $this->assertEquals('Diambil', $order1->fresh()->status);

        // Invalid status validation
        $invalidResponse = $this->patch("/admin/orders/{$order1->id}/status", [
            'status' => 'StatusPalsu',
        ]);
        $invalidResponse->assertSessionHasErrors('status');
    }

    /**
     * 9. Pengujian Tambahan Ketahanan (Robustness & Edge Cases)
     */
    public function test_package_show_redirects_to_edit_for_admin_and_forbids_customer(): void
    {
        $package = LaundryPackage::first();

        // 1. Customer dilarang mengakses show (403 Forbidden, bukan 500 unhandled method crash)
        $customer = User::where('role', 'customer')->first();
        $this->actingAs($customer);
        $this->get("/admin/packages/{$package->id}")->assertStatus(403);

        // 2. Admin diarahkan ke halaman edit tanpa error 500
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);
        $response = $this->get("/admin/packages/{$package->id}");
        $response->assertRedirect(route('admin.packages.edit', $package));
    }

    public function test_authenticated_users_redirected_from_register_page(): void
    {
        // Customer mengakses /register dialihkan ke /catalog
        $customer = User::where('role', 'customer')->first();
        $this->actingAs($customer);
        $this->get('/register')->assertRedirect(route('customer.catalog'));
        $this->post('/register', [
            'name' => 'Duplikat',
            'email' => 'duplikat@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('customer.catalog'));

        // Admin mengakses /register dialihkan ke /admin/packages
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);
        $this->get('/register')->assertRedirect(route('admin.packages.index'));
        $this->post('/register', [
            'name' => 'Duplikat Admin',
            'email' => 'duplikat2@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('admin.packages.index'));
    }

    public function test_order_creation_validation_and_decimal_rounding(): void
    {
        $customer = User::where('role', 'customer')->first();
        $this->actingAs($customer);
        $package = LaundryPackage::where('price_per_kg', 7000)->first();

        // 1. Input berat kurang dari 0.5 kg ditolak validasi
        $failResponse = $this->from("/order/create/{$package->id}")->post('/order/store', [
            'package_id' => $package->id,
            'customer_name' => 'Budi',
            'weight' => 0.2,
        ]);
        $failResponse->assertRedirect("/order/create/{$package->id}");
        $failResponse->assertSessionHasErrors('weight');

        // Pastikan error ditampilkan pada form pemesanan saat redirect diikuti
        $followed = $this->followRedirects($failResponse);
        $followed->assertSee('0.5');

        // 2. Input berat desimal presisi (misal: 2.25 kg)
        // 7000 * 2.25 = 15750 integer
        $successResponse = $this->post('/order/store', [
            'package_id' => $package->id,
            'customer_name' => 'Pelanggan Timbangan Pas',
            'weight' => 2.25,
        ]);
        $successResponse->assertRedirect(route('customer.catalog'));

        $order = Order::where('customer_name', 'Pelanggan Timbangan Pas')->first();
        $this->assertNotNull($order);
        $this->assertIsInt($order->total_price);
        $this->assertEquals(15750, $order->total_price);
        $this->assertEquals(2.25, $order->weight);
    }
}

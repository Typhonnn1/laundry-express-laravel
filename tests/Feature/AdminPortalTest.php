<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPortalTest extends TestCase
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
                'description' => 'Cuci, kering, setrika rapi.',
            ]),
            Service::create([
                'service_name' => 'Bed Cover King Size',
                'category' => 'Satuan',
                'price_per_unit' => 25000,
                'unit_type' => 'Pcs',
                'description' => 'Cuci bed cover ukuran besar.',
            ]),
            Service::create([
                'service_name' => 'Cuci Kilat Express 3 Jam',
                'category' => 'Express',
                'price_per_unit' => 15000,
                'unit_type' => 'Kg',
                'description' => 'Express 3 jam selesai.',
            ]),
        ];
    }

    protected function createOrderWithItems(string $name, string $phone, array $items, string $status = 'Antrean'): Order
    {
        $order = Order::create([
            'customer_name' => $name,
            'whatsapp_number' => $phone,
            'total_price' => 0,
            'status' => $status,
        ]);

        $grandTotal = 0;
        foreach ($items as $item) {
            $service = $item['service'];
            $qty = $item['qty'];
            $subtotal = $service->price_per_unit * $qty;
            $grandTotal += $subtotal;

            OrderDetail::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'qty_or_weight' => $qty,
                'subtotal' => $subtotal,
            ]);
        }

        $order->update(['total_price' => $grandTotal]);

        return $order;
    }

    // =========================================================================
    // 1. AUTHENTICATION & ACCESS CONTROL
    // =========================================================================

    public function test_unauthenticated_access_to_admin_routes_redirects_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/layanan')->assertRedirect('/login');
        $this->post('/admin/layanan', [])->assertRedirect('/login');
        $this->get('/admin/pesanan')->assertRedirect('/login');
        $this->patch('/admin/pesanan/1/status', [])->assertRedirect('/login');
        $this->delete('/admin/pesanan/1')->assertRedirect('/login');
    }

    public function test_admin_login_with_valid_credentials_succeeds_and_redirects_to_admin(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_with_invalid_password_fails_with_error(): void
    {
        $this->createAdminUser();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_admin_logout_clears_session_and_redirects_to_login(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    // =========================================================================
    // 2. DASHBOARD METRICS
    // =========================================================================

    public function test_dashboard_displays_counts_and_revenue(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        // 7000 * 2 = 14000 (Antrean)
        $this->createOrderWithItems('Cust Antrean', '08111111111', [['service' => $services[0], 'qty' => 2]], 'Antrean');
        // 25000 * 1 = 25000 (Dicuci)
        $this->createOrderWithItems('Cust Dicuci', '08222222222', [['service' => $services[1], 'qty' => 1]], 'Dicuci');
        // 15000 * 1 = 15000 (Selesai)
        $this->createOrderWithItems('Cust Selesai', '08333333333', [['service' => $services[2], 'qty' => 1]], 'Selesai');
        // Total revenue: 54.000

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Total Pesanan', false);
        $response->assertSee('54.000', false);
        $response->assertSee('Antrean', false);
        $response->assertSee('Dicuci', false);
        $response->assertSee('Selesai', false);
        $response->assertSee('Cust Antrean', false);
    }

    // =========================================================================
    // 3. SERVICE PACKAGE CRUD
    // =========================================================================

    public function test_admin_can_view_services_list(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $response = $this->actingAs($admin)->get('/admin/layanan');

        $response->assertStatus(200);
        foreach ($services as $svc) {
            $response->assertSee($svc->service_name, false);
        }
        $response->assertSee('Kiloan', false);
        $response->assertSee('Satuan', false);
        $response->assertSee('Express', false);
        $response->assertSee('Edit', false);
        $response->assertSee('Hapus', false);
        $response->assertSee('Tambah', false);
    }

    public function test_admin_can_create_new_service_with_validation(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Cuci Sepatu Premium',
            'category' => 'Satuan',
            'price_per_unit' => 30000,
            'unit_type' => 'Pcs',
            'description' => 'Pembersihan mendalam sepatu.',
        ]);

        $response->assertRedirect('/admin/layanan');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('services', [
            'service_name' => 'Cuci Sepatu Premium',
            'price_per_unit' => 30000,
        ]);
    }

    public function test_service_creation_validation_rules_reject_invalid_data(): void
    {
        $admin = $this->createAdminUser();

        // Negative price rejected
        $resPrice = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Bad Price',
            'category' => 'Kiloan',
            'price_per_unit' => -500,
            'unit_type' => 'Kg',
        ]);
        $resPrice->assertSessionHasErrors('price_per_unit');

        // Invalid category enum rejected
        $resCat = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Bad Cat',
            'category' => 'Unknown',
            'price_per_unit' => 5000,
            'unit_type' => 'Kg',
        ]);
        $resCat->assertSessionHasErrors('category');

        // Invalid unit type enum rejected
        $resUnit = $this->actingAs($admin)->post('/admin/layanan', [
            'service_name' => 'Bad Unit',
            'category' => 'Kiloan',
            'price_per_unit' => 5000,
            'unit_type' => 'Meter',
        ]);
        $resUnit->assertSessionHasErrors('unit_type');
    }

    public function test_admin_can_update_service_details(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $target = $services[0];

        $response = $this->actingAs($admin)->put("/admin/layanan/{$target->id}", [
            'service_name' => 'Cuci Komplit Kilat Updated',
            'category' => 'Express',
            'price_per_unit' => 12500,
            'unit_type' => 'Kg',
            'description' => 'Paket kilat update.',
        ]);

        $response->assertRedirect('/admin/layanan');
        $response->assertSessionHas('success');
        $this->assertEquals(12500, $target->fresh()->price_per_unit);
        $this->assertEquals('Express', $target->fresh()->category);
    }

    public function test_admin_can_delete_unused_service(): void
    {
        $admin = $this->createAdminUser();
        $service = Service::create([
            'service_name' => 'Paket Khusus Sementara',
            'category' => 'Satuan',
            'price_per_unit' => 40000,
            'unit_type' => 'Pcs',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/layanan/{$service->id}");

        $response->assertRedirect('/admin/layanan');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    // =========================================================================
    // 4. ORDER WORKFLOW & STATUS MANAGEMENT
    // =========================================================================

    public function test_admin_can_view_orders_list_with_filters_and_search(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $orderAntrean = $this->createOrderWithItems('Budi Santoso', '081234567890', [['service' => $services[0], 'qty' => 1]], 'Antrean');
        $orderSelesai = $this->createOrderWithItems('Siti Rahmawati', '081298765432', [['service' => $services[1], 'qty' => 1]], 'Selesai');

        // All orders
        $resAll = $this->actingAs($admin)->get('/admin/pesanan');
        $resAll->assertStatus(200);
        $resAll->assertSee('Budi Santoso', false);
        $resAll->assertSee('Siti Rahmawati', false);

        // Filter by status Antrean
        $resAntrean = $this->actingAs($admin)->get('/admin/pesanan?status=Antrean');
        $resAntrean->assertSee('Budi Santoso', false);

        // Filter by status Selesai
        $resSelesai = $this->actingAs($admin)->get('/admin/pesanan?status=Selesai');
        $resSelesai->assertSee('Siti Rahmawati', false);

        // Search by keyword Siti
        $resSearch = $this->actingAs($admin)->get('/admin/pesanan?search=Siti');
        $resSearch->assertSee('Siti Rahmawati', false);
    }

    public function test_admin_can_update_order_status_and_reflects_in_customer_tracking(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Customer Tracking Check', '081255556666', [['service' => $services[0], 'qty' => 1]], 'Antrean');

        // Transition Antrean -> Dicuci
        $res1 = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Dicuci',
        ]);
        $res1->assertStatus(302);
        $res1->assertSessionHas('success');
        $this->assertEquals('Dicuci', $order->fresh()->status);

        // Transition Dicuci -> Selesai
        $res2 = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Selesai',
        ]);
        $res2->assertStatus(302);
        $this->assertEquals('Selesai', $order->fresh()->status);

        // Verify customer tracking reflects Selesai
        $resTrack = $this->get('/?search=081255556666');
        $resTrack->assertSee('Selesai', false);
    }

    public function test_order_status_update_rejects_invalid_status(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Invalid Status Order', '081234567890', [['service' => $services[0], 'qty' => 1]], 'Antrean');

        $response = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => 'Dibatalkan', // Not in ['Antrean', 'Dicuci', 'Selesai']
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals('Antrean', $order->fresh()->status);
    }

    public function test_admin_can_delete_order_and_cascades_to_order_details(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Deleted Cust', '081211119999', [
            ['service' => $services[0], 'qty' => 2],
            ['service' => $services[1], 'qty' => 1],
        ]);

        $orderDetailIds = $order->orderDetails->pluck('id')->toArray();
        $this->assertNotEmpty($orderDetailIds);

        $response = $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");

        $response->assertRedirect('/admin/pesanan');
        $response->assertSessionHas('success');

        // Order record deleted
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);

        // Cascaded details deleted
        foreach ($orderDetailIds as $id) {
            $this->assertDatabaseMissing('order_details', ['id' => $id]);
        }
    }

    public function test_deleted_order_is_removed_from_customer_tracking(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithItems('Tracking Ghost Cust', '081299997777', [['service' => $services[0], 'qty' => 1]]);

        $this->actingAs($admin)->delete("/admin/pesanan/{$order->id}");

        $response = $this->get('/?search=081299997777');
        $response->assertDontSee('Tracking Ghost Cust');
    }
}

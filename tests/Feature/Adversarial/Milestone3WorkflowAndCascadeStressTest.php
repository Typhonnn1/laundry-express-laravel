<?php

namespace Tests\Feature\Adversarial;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Milestone 3 Adversarial Stress Test Suite: Data Workflows, Cascade Integrity & Volume
 *
 * Empirical verification covering:
 * 1. Order status progression cycles (Antrean -> Dicuci -> Selesai) with tracking bar percentages (33%, 66%, 100%) and badge classes.
 * 2. High-volume batch order management (50 orders), pagination thresholds, status count tabs, and keyword searches.
 * 3. Cascade deletion integrity: clean removal of order_details without foreign-key orphan rows, and dashboard revenue/order count decrement.
 * 4. Master service deletion safety and price update isolation (historical order subtotal & total price immutability).
 */
class Milestone3WorkflowAndCascadeStressTest extends TestCase
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
            'kiloan' => Service::create([
                'service_name' => 'Cuci Komplit Reguler',
                'category' => 'Kiloan',
                'price_per_unit' => 7000,
                'unit_type' => 'Kg',
                'description' => 'Cuci, kering, dan setrika rapi.',
            ]),
            'satuan' => Service::create([
                'service_name' => 'Bed Cover King Size',
                'category' => 'Satuan',
                'price_per_unit' => 25000,
                'unit_type' => 'Pcs',
                'description' => 'Cuci bed cover ukuran besar.',
            ]),
            'express' => Service::create([
                'service_name' => 'Cuci Kilat Express 3 Jam',
                'category' => 'Express',
                'price_per_unit' => 15000,
                'unit_type' => 'Kg',
                'description' => 'Express 3 jam selesai.',
            ]),
        ];
    }

    protected function createOrderWithDetails(string $name, string $phone, array $items, string $status = 'Antrean'): Order
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
            $qty = (int) $item['qty'];
            $subtotal = $service->price_per_unit * $qty;
            $total += $subtotal;

            OrderDetail::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'qty_or_weight' => $qty,
                'subtotal' => $subtotal,
            ]);
        }

        $order->update(['total_price' => $total]);

        return $order;
    }

    // =========================================================================
    // 1. ORDER STATUS PROGRESSION CYCLES & TRACKING BAR / BADGE VERIFICATION
    // =========================================================================

    public function test_order_status_progression_cycles_and_tracking_bar_badge_synchronization(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        // 1. Initial State: Antrean
        $order = $this->createOrderWithDetails(
            'Haryono Kusumo',
            '081234567890',
            [
                ['service' => $services['kiloan'], 'qty' => 3], // 21.000
                ['service' => $services['satuan'], 'qty' => 1], // 25.000 -> Total: 46.000
            ],
            Order::STATUS_ANTREAN
        );

        $this->assertEquals(Order::STATUS_ANTREAN, $order->status);
        $this->assertEquals(33, $order->progress_percentage);
        $this->assertEquals('warning text-dark', $order->status_badge_class);

        // Verify public tracking view for Antrean
        $trackResAntrean = $this->get('/?search='.$order->whatsapp_number);
        $trackResAntrean->assertStatus(200);
        $trackResAntrean->assertSee('aria-valuenow="33"', false);
        $trackResAntrean->assertSee('width: 33%;', false);
        $trackResAntrean->assertSee('Antrean', false);
        $trackResAntrean->assertSee('bg-warning text-dark', false);
        $trackResAntrean->assertSee('Pesanan Anda telah tercatat dan sedang berada dalam antrean penanganan outlet.', false);

        // 2. Transition Antrean -> Dicuci
        $patchToDicuci = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => Order::STATUS_DICUCI,
        ]);
        $patchToDicuci->assertStatus(302);
        $patchToDicuci->assertSessionHas('success', 'Status pesanan berhasil diperbarui.');

        $order->refresh();
        $this->assertEquals(Order::STATUS_DICUCI, $order->status);
        $this->assertEquals(66, $order->progress_percentage);
        $this->assertEquals('info text-dark', $order->status_badge_class);

        // Verify public tracking view for Dicuci (Search by ID)
        $trackResDicuci = $this->get('/?search='.$order->id);
        $trackResDicuci->assertStatus(200);
        $trackResDicuci->assertSee('aria-valuenow="66"', false);
        $trackResDicuci->assertSee('width: 66%;', false);
        $trackResDicuci->assertSee('Dicuci', false);
        $trackResDicuci->assertSee('bg-info text-dark', false);
        $trackResDicuci->assertSee('Pakaian Anda sedang dalam proses pencucian, pengeringan, dan penyetrikaan higienis.', false);

        // 3. Transition Dicuci -> Selesai
        $patchToSelesai = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => Order::STATUS_SELESAI,
        ]);
        $patchToSelesai->assertStatus(302);
        $patchToSelesai->assertSessionHas('success', 'Status pesanan berhasil diperbarui.');

        $order->refresh();
        $this->assertEquals(Order::STATUS_SELESAI, $order->status);
        $this->assertEquals(100, $order->progress_percentage);
        $this->assertEquals('success', $order->status_badge_class);

        // Verify public tracking view for Selesai
        $trackResSelesai = $this->get('/?search='.$order->whatsapp_number);
        $trackResSelesai->assertStatus(200);
        $trackResSelesai->assertSee('aria-valuenow="100"', false);
        $trackResSelesai->assertSee('width: 100%;', false);
        $trackResSelesai->assertSee('Selesai', false);
        $trackResSelesai->assertSee('bg-success', false);
        $trackResSelesai->assertSee('Cucian Anda telah selesai! Silakan mengambil pakaian di outlet kami', false);

        // 4. Reverse Transition Selesai -> Antrean (Operational Reset)
        $patchReset = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
            'status' => Order::STATUS_ANTREAN,
        ]);
        $patchReset->assertStatus(302);

        $order->refresh();
        $this->assertEquals(Order::STATUS_ANTREAN, $order->status);
        $this->assertEquals(33, $order->progress_percentage);

        $trackResReset = $this->get('/?search='.$order->id);
        $trackResReset->assertSee('aria-valuenow="33"', false);
        $trackResReset->assertSee('width: 33%;', false);
    }

    public function test_order_status_transition_enforces_validation_and_authorization(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();
        $order = $this->createOrderWithDetails(
            'Unauthorized Test',
            '081299990000',
            [['service' => $services['kiloan'], 'qty' => 1]],
            Order::STATUS_ANTREAN
        );

        // Unauthenticated guest cannot update status
        $guestAttempt = $this->patch("/admin/pesanan/{$order->id}/status", [
            'status' => Order::STATUS_DICUCI,
        ]);
        $guestAttempt->assertRedirect('/login');
        $this->assertEquals(Order::STATUS_ANTREAN, $order->fresh()->status);

        // Admin cannot submit invalid or malformed statuses
        $invalidStatuses = ['Dibatalkan', 'Pending', 'Batal', 'Unknown', '', '123'];
        foreach ($invalidStatuses as $invalidStatus) {
            $invalidAttempt = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
                'status' => $invalidStatus,
            ]);
            $invalidAttempt->assertSessionHasErrors('status');
            $this->assertEquals(Order::STATUS_ANTREAN, $order->fresh()->status);
        }

        // Updating nonexistent order returns 404
        $notFoundAttempt = $this->actingAs($admin)->patch('/admin/pesanan/999999/status', [
            'status' => Order::STATUS_DICUCI,
        ]);
        $notFoundAttempt->assertStatus(404);
    }

    // =========================================================================
    // 2. BATCH ORDER VOLUME STRESS TEST (50 ORDERS, PAGINATION, TABS, SEARCH)
    // =========================================================================

    public function test_batch_order_volume_fifty_orders_pagination_tabs_and_search(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        // Generate 50 orders with varying status distribution:
        // - 20 Antrean (IDs 1-20)
        // - 18 Dicuci (IDs 21-38)
        // - 12 Selesai (IDs 39-50)
        $createdOrders = [];
        for ($i = 1; $i <= 50; $i++) {
            if ($i <= 20) {
                $status = Order::STATUS_ANTREAN;
            } elseif ($i <= 38) {
                $status = Order::STATUS_DICUCI;
            } else {
                $status = Order::STATUS_SELESAI;
            }

            // Assign unique identifiable customer data
            if ($i === 15) {
                $custName = 'Budi Super VIP';
                $custPhone = '089988776655';
            } elseif ($i === 35) {
                $custName = 'Siti Khadijah';
                $custPhone = '087711223344';
            } elseif ($i === 45) {
                $custName = 'Dewi Sartika';
                $custPhone = '088855443322';
            } else {
                $custName = "Pelanggan Batch {$i}";
                $custPhone = '0812'.str_pad($i, 8, '0', STR_PAD_LEFT);
            }

            $serviceKey = match ($i % 3) {
                0 => 'kiloan',
                1 => 'satuan',
                default => 'express',
            };

            $createdOrders[] = $this->createOrderWithDetails(
                $custName,
                $custPhone,
                [['service' => $services[$serviceKey], 'qty' => ($i % 5) + 1]],
                $status
            );
        }

        $this->assertDatabaseCount('orders', 50);

        // 1. Verify Default Orders Page 1 Pagination (25 orders per page)
        $resPage1 = $this->actingAs($admin)->get('/admin/pesanan');
        $resPage1->assertStatus(200);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $resPage1->viewData('orders');
        $this->assertEquals(50, $paginator->total());
        $this->assertEquals(25, $paginator->perPage());
        $this->assertEquals(1, $paginator->currentPage());
        $this->assertCount(25, $paginator->items());

        // Verify Status Count Tabs in DOM
        $statusCounts = $resPage1->viewData('statusCounts');
        $this->assertEquals(50, $statusCounts['all']);
        $this->assertEquals(20, $statusCounts['Antrean']);
        $this->assertEquals(18, $statusCounts['Dicuci']);
        $this->assertEquals(12, $statusCounts['Selesai']);

        $resPage1->assertSee('Semua <span class="badge bg-white text-primary ms-1">50</span>', false);
        $resPage1->assertSee('Antrean', false);
        $resPage1->assertSee('>20<', false);
        $resPage1->assertSee('Dicuci', false);
        $resPage1->assertSee('>18<', false);
        $resPage1->assertSee('Selesai', false);
        $resPage1->assertSee('>12<', false);

        // 2. Verify Page 2 Pagination
        $resPage2 = $this->actingAs($admin)->get('/admin/pesanan?page=2');
        $resPage2->assertStatus(200);
        $paginator2 = $resPage2->viewData('orders');
        $this->assertEquals(2, $paginator2->currentPage());
        $this->assertCount(25, $paginator2->items());

        // 3. Verify Status Filter: Antrean (20 items)
        $resFilterAntrean = $this->actingAs($admin)->get('/admin/pesanan?status=Antrean');
        $resFilterAntrean->assertStatus(200);
        $paginatorAntrean = $resFilterAntrean->viewData('orders');
        $this->assertEquals(20, $paginatorAntrean->total());
        foreach ($paginatorAntrean->items() as $item) {
            $this->assertEquals(Order::STATUS_ANTREAN, $item->status);
        }

        // 4. Verify Status Filter: Dicuci (18 items)
        $resFilterDicuci = $this->actingAs($admin)->get('/admin/pesanan?status=Dicuci');
        $resFilterDicuci->assertStatus(200);
        $paginatorDicuci = $resFilterDicuci->viewData('orders');
        $this->assertEquals(18, $paginatorDicuci->total());
        foreach ($paginatorDicuci->items() as $item) {
            $this->assertEquals(Order::STATUS_DICUCI, $item->status);
        }

        // 5. Verify Status Filter: Selesai (12 items)
        $resFilterSelesai = $this->actingAs($admin)->get('/admin/pesanan?status=Selesai');
        $resFilterSelesai->assertStatus(200);
        $paginatorSelesai = $resFilterSelesai->viewData('orders');
        $this->assertEquals(12, $paginatorSelesai->total());
        foreach ($paginatorSelesai->items() as $item) {
            $this->assertEquals(Order::STATUS_SELESAI, $item->status);
        }

        // 6. Keyword Searches across customer names, phone numbers, and IDs
        // Search Name
        $searchName = $this->actingAs($admin)->get('/admin/pesanan?search=Budi+Super+VIP');
        $searchName->assertStatus(200);
        $searchName->assertSee('Budi Super VIP', false);
        $searchName->assertSee('089988776655', false);
        $this->assertEquals(1, $searchName->viewData('orders')->total());

        // Search Phone
        $searchPhone = $this->actingAs($admin)->get('/admin/pesanan?search=087711223344');
        $searchPhone->assertStatus(200);
        $searchPhone->assertSee('Siti Khadijah', false);
        $this->assertEquals(1, $searchPhone->viewData('orders')->total());

        // Search Order ID (Exact ID)
        $targetOrder = $createdOrders[44]; // 45th order: Dewi Sartika
        $searchId = $this->actingAs($admin)->get('/admin/pesanan?search='.$targetOrder->id);
        $searchId->assertStatus(200);
        $searchId->assertSee('Dewi Sartika', false);
        $this->assertEquals(1, $searchId->viewData('orders')->total());

        // Search Formatted ID (#LE-00045)
        $searchFormattedId = $this->actingAs($admin)->get('/admin/pesanan?search=%23LE-'.str_pad($targetOrder->id, 5, '0', STR_PAD_LEFT));
        $searchFormattedId->assertStatus(200);
        $searchFormattedId->assertSee('Dewi Sartika', false);
        $this->assertEquals(1, $searchFormattedId->viewData('orders')->total());

        // Combined Search + Status Filter (Matching)
        $searchCombinedMatch = $this->actingAs($admin)->get('/admin/pesanan?status=Antrean&search=Budi+Super+VIP');
        $searchCombinedMatch->assertStatus(200);
        $this->assertEquals(1, $searchCombinedMatch->viewData('orders')->total());

        // Combined Search + Status Filter (Mismatch -> 0 items)
        $searchCombinedMismatch = $this->actingAs($admin)->get('/admin/pesanan?status=Selesai&search=Budi+Super+VIP');
        $searchCombinedMismatch->assertStatus(200);
        $this->assertEquals(0, $searchCombinedMismatch->viewData('orders')->total());
        $searchCombinedMismatch->assertSee('Tidak ada data pesanan yang sesuai dengan filter atau kata kunci pencarian.', false);
    }

    // =========================================================================
    // 3. CASCADE DELETION INTEGRITY & FINANCIAL METRIC RECONCILIATION
    // =========================================================================

    public function test_cascade_deletion_removes_order_details_without_orphan_rows_and_decrements_metrics(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        // Create 8 multi-item orders
        $orders = [];
        for ($i = 1; $i <= 8; $i++) {
            $orders[] = $this->createOrderWithDetails(
                "Cascade Customer {$i}",
                "08125555000{$i}",
                [
                    ['service' => $services['kiloan'], 'qty' => 2], // 14.000
                    ['service' => $services['satuan'], 'qty' => 1], // 25.000
                    ['service' => $services['express'], 'qty' => 1], // 15.000 -> Total per order: 54.000
                ],
                $i % 2 === 0 ? Order::STATUS_ANTREAN : Order::STATUS_DICUCI
            );
        }

        $initialTotalOrders = Order::count(); // 8
        $initialTotalDetails = OrderDetail::count(); // 8 * 3 = 24
        $initialRevenue = (int) Order::sum('total_price'); // 8 * 54.000 = 432.000

        $this->assertEquals(8, $initialTotalOrders);
        $this->assertEquals(24, $initialTotalDetails);
        $this->assertEquals(432000, $initialRevenue);

        // Verify initial dashboard displays exact metric values
        $initDash = $this->actingAs($admin)->get('/admin');
        $initDash->assertStatus(200);
        $initDash->assertSee('432.000', false);
        $this->assertEquals(8, $initDash->viewData('totalOrders'));
        $this->assertEquals(432000, $initDash->viewData('totalRevenue'));

        // Select 3 orders to delete
        $ordersToDelete = [$orders[0], $orders[2], $orders[5]];
        $deletedOrderIds = array_map(fn ($o) => $o->id, $ordersToDelete);
        $deletedDetailIds = OrderDetail::whereIn('order_id', $deletedOrderIds)->pluck('id')->toArray();
        $this->assertCount(9, $deletedDetailIds); // 3 orders * 3 items = 9 detail rows
        $expectedRevenueDrop = 3 * 54000; // 162.000

        // Delete the orders via Admin endpoint
        foreach ($ordersToDelete as $orderToDelete) {
            $deleteResponse = $this->actingAs($admin)->delete("/admin/pesanan/{$orderToDelete->id}");
            $deleteResponse->assertRedirect('/admin/pesanan');
            $deleteResponse->assertSessionHas('success', 'Pesanan berhasil dihapus.');
        }

        // Database assertions for orders
        foreach ($deletedOrderIds as $deletedId) {
            $this->assertDatabaseMissing('orders', ['id' => $deletedId]);
        }
        $this->assertDatabaseCount('orders', 5);

        // Relational database assertions for order_details: NO ORPHAN ROWS
        foreach ($deletedDetailIds as $deletedDetailId) {
            $this->assertDatabaseMissing('order_details', ['id' => $deletedDetailId]);
        }
        $this->assertEquals(15, OrderDetail::count()); // 24 - 9 = 15 remaining
        $this->assertEquals(0, OrderDetail::whereIn('order_id', $deletedOrderIds)->count());

        // Financial & Dashboard Metrics Decrement Check
        $expectedNewRevenue = 432000 - $expectedRevenueDrop; // 270.000
        $this->assertEquals(270000, (int) Order::sum('total_price'));

        $postDash = $this->actingAs($admin)->get('/admin');
        $postDash->assertStatus(200);
        $this->assertEquals(5, $postDash->viewData('totalOrders'));
        $this->assertEquals(270000, $postDash->viewData('totalRevenue'));
        $postDash->assertSee('270.000', false);

        // Verification on Public Portal: Receipts 404, Tracking not found
        foreach ($ordersToDelete as $deletedOrder) {
            $receiptResponse = $this->get('/nota/'.$deletedOrder->id);
            $receiptResponse->assertStatus(404);

            $trackingResponse = $this->get('/?search='.$deletedOrder->whatsapp_number);
            $trackingResponse->assertStatus(200);
            $trackingResponse->assertSee('Pesanan tidak ditemukan', false);
            $trackingResponse->assertDontSee($deletedOrder->customer_name);
        }
    }

    // =========================================================================
    // 4. MASTER SERVICE DELETION SAFETY & PRICE UPDATE ISOLATION
    // =========================================================================

    public function test_service_price_alteration_preserves_historical_order_subtotals_and_revenue(): void
    {
        $admin = $this->createAdminUser();

        // 1. Create a specialized service
        $service = Service::create([
            'service_name' => 'Layanan Cuci Karpet Masjid',
            'category' => 'Satuan',
            'price_per_unit' => 30000,
            'unit_type' => 'Pcs',
            'description' => 'Pencucian karpet tebal.',
        ]);

        // 2. Customer places historical order: 4 Pcs @ 30.000 = 120.000
        $historicalOrder = $this->createOrderWithDetails(
            'Pengurus Masjid Al-Ikhlas',
            '081277665544',
            [['service' => $service, 'qty' => 4]],
            Order::STATUS_ANTREAN
        );

        $this->assertEquals(120000, $historicalOrder->total_price);
        $historicalDetail = $historicalOrder->orderDetails->first();
        $this->assertEquals(120000, $historicalDetail->subtotal);

        // Verify receipt shows 120.000
        $receiptBefore = $this->get('/nota/'.$historicalOrder->id);
        $receiptBefore->assertStatus(200);
        $receiptBefore->assertSee('120.000', false);

        // Verify dashboard shows 120.000
        $dashBefore = $this->actingAs($admin)->get('/admin');
        $dashBefore->assertSee('120.000', false);

        // 3. Admin alters the master service price: increases from 30.000 to 75.000 (+150%)
        $updateResponse = $this->actingAs($admin)->put("/admin/layanan/{$service->id}", [
            'service_name' => 'Layanan Cuci Karpet Masjid Premium Plus',
            'category' => 'Express',
            'price_per_unit' => 75000,
            'unit_type' => 'Pcs',
            'description' => 'Pencucian karpet cepat 4 jam.',
        ]);
        $updateResponse->assertRedirect('/admin/layanan');
        $updateResponse->assertSessionHas('success', 'Paket layanan berhasil diperbarui.');

        $service->refresh();
        $this->assertEquals(75000, $service->price_per_unit);
        $this->assertEquals('Express', $service->category);

        // 4. Assert Historical Order Immuntability:
        // Detail subtotal and grand total in database must remain 120.000 (NOT 300.000!)
        $historicalOrder->refresh();
        $historicalDetail->refresh();
        $this->assertEquals(120000, $historicalOrder->total_price);
        $this->assertEquals(120000, $historicalDetail->subtotal);

        // Historical receipt must continue displaying 120.000
        $receiptAfter = $this->get('/nota/'.$historicalOrder->id);
        $receiptAfter->assertStatus(200);
        $receiptAfter->assertSee('120.000', false);

        // Admin order table must continue displaying 120.000
        $ordersTable = $this->actingAs($admin)->get('/admin/pesanan');
        $ordersTable->assertStatus(200);
        $ordersTable->assertSee('120.000', false);

        // Dashboard revenue must remain 120.000
        $dashAfter = $this->actingAs($admin)->get('/admin');
        $dashAfter->assertSee('120.000', false);

        // 5. Subsequent new order uses the new price rate (75.000)
        $newOrder = $this->createOrderWithDetails(
            'Jamaah Pengajian Baru',
            '081299887766',
            [['service' => $service, 'qty' => 2]], // 2 * 75.000 = 150.000
            Order::STATUS_ANTREAN
        );

        $this->assertEquals(150000, $newOrder->total_price);

        // Financial reconciliation across both orders: 120.000 + 150.000 = 270.000
        $this->assertEquals(270000, (int) Order::sum('total_price'));
        $dashCombined = $this->actingAs($admin)->get('/admin');
        $dashCombined->assertSee('270.000', false);
    }

    public function test_service_deletion_safety_with_and_without_order_dependencies(): void
    {
        $admin = $this->createAdminUser();

        // 1. Unused Service Deletion
        $unusedService = Service::create([
            'service_name' => 'Paket Musiman Sementara',
            'category' => 'Satuan',
            'price_per_unit' => 50000,
            'unit_type' => 'Pcs',
        ]);

        $deleteUnused = $this->actingAs($admin)->delete("/admin/layanan/{$unusedService->id}");
        $deleteUnused->assertRedirect('/admin/layanan');
        $deleteUnused->assertSessionHas('success', 'Paket layanan berhasil dihapus.');
        $this->assertDatabaseMissing('services', ['id' => $unusedService->id]);

        // 2. Service with Order Dependency Deletion
        $activeService = Service::create([
            'service_name' => 'Layanan Seragam Pabrik',
            'category' => 'Kiloan',
            'price_per_unit' => 6000,
            'unit_type' => 'Kg',
        ]);

        $order = $this->createOrderWithDetails(
            'PT Mandiri Tekstil',
            '081233445566',
            [['service' => $activeService, 'qty' => 10]], // 60.000
            Order::STATUS_ANTREAN
        );

        $this->assertEquals(60000, $order->total_price);
        $detailId = $order->orderDetails->first()->id;

        // Admin deletes the service package
        $deleteActive = $this->actingAs($admin)->delete("/admin/layanan/{$activeService->id}");
        $deleteActive->assertRedirect('/admin/layanan');
        $deleteActive->assertSessionHas('success', 'Paket layanan berhasil dihapus.');

        // Verify service removed
        $this->assertDatabaseMissing('services', ['id' => $activeService->id]);

        // Foreign key constraint cascade deletes the detail record cleanly
        $this->assertDatabaseMissing('order_details', ['id' => $detailId]);

        // The order master row remains intact in orders table with recorded total_price
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'total_price' => 60000,
        ]);

        // Admin orders list page loads cleanly without null reference exceptions
        $ordersPage = $this->actingAs($admin)->get('/admin/pesanan');
        $ordersPage->assertStatus(200);
        $ordersPage->assertSee('PT Mandiri Tekstil', false);
        $ordersPage->assertSee('60.000', false);

        // Dashboard revenue remains preserved
        $dashRes = $this->actingAs($admin)->get('/admin');
        $dashRes->assertStatus(200);
        $dashRes->assertSee('60.000', false);
    }

    public function test_rapid_status_progression_across_multiple_orders(): void
    {
        $admin = $this->createAdminUser();
        $services = $this->createStandardServices();

        $orders = [];
        for ($i = 1; $i <= 10; $i++) {
            $orders[] = $this->createOrderWithDetails(
                "Rapid Customer {$i}",
                "08128888000{$i}",
                [['service' => $services['kiloan'], 'qty' => 2]],
                Order::STATUS_ANTREAN
            );
        }

        // Advance all 10 orders from Antrean to Dicuci
        foreach ($orders as $order) {
            $res = $this->actingAs($admin)->patch("/admin/pesanan/{$order->id}/status", [
                'status' => Order::STATUS_DICUCI,
            ]);
            $res->assertStatus(302);
            $this->assertEquals(Order::STATUS_DICUCI, $order->fresh()->status);
            $this->assertEquals(66, $order->fresh()->progress_percentage);
        }

        // Advance 5 of them to Selesai
        for ($i = 0; $i < 5; $i++) {
            $res = $this->actingAs($admin)->patch("/admin/pesanan/{$orders[$i]->id}/status", [
                'status' => Order::STATUS_SELESAI,
            ]);
            $res->assertStatus(302);
            $this->assertEquals(Order::STATUS_SELESAI, $orders[$i]->fresh()->status);
            $this->assertEquals(100, $orders[$i]->fresh()->progress_percentage);
        }

        // Verify status distribution in database and admin orders view
        $this->assertEquals(0, Order::where('status', Order::STATUS_ANTREAN)->count());
        $this->assertEquals(5, Order::where('status', Order::STATUS_DICUCI)->count());
        $this->assertEquals(5, Order::where('status', Order::STATUS_SELESAI)->count());

        $ordersView = $this->actingAs($admin)->get('/admin/pesanan');
        $ordersView->assertStatus(200);
        $counts = $ordersView->viewData('statusCounts');
        $this->assertEquals(10, $counts['all']);
        $this->assertEquals(0, $counts['Antrean']);
        $this->assertEquals(5, $counts['Dicuci']);
        $this->assertEquals(5, $counts['Selesai']);
    }
}

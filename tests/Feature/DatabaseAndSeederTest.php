<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseAndSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all required database tables are created by migrations.
     */
    public function test_migrations_create_expected_tables(): void
    {
        $this->assertTrue(Schema::hasTable('users'), 'Table users does not exist.');
        $this->assertTrue(Schema::hasTable('services'), 'Table services does not exist.');
        $this->assertTrue(Schema::hasTable('orders'), 'Table orders does not exist.');
        $this->assertTrue(Schema::hasTable('order_details'), 'Table order_details does not exist.');
    }

    /**
     * Test that the users table has all required columns.
     */
    public function test_users_table_has_expected_columns(): void
    {
        $expectedColumns = [
            'id',
            'name',
            'username',
            'email',
            'email_verified_at',
            'password',
            'remember_token',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Column '{$column}' is missing from users table."
            );
        }
    }

    /**
     * Test that the services table has all required columns.
     */
    public function test_services_table_has_expected_columns(): void
    {
        $expectedColumns = [
            'id',
            'service_name',
            'category',
            'price_per_unit',
            'unit_type',
            'description',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('services', $column),
                "Column '{$column}' is missing from services table."
            );
        }
    }

    /**
     * Test that the orders table has all required columns.
     */
    public function test_orders_table_has_expected_columns(): void
    {
        $expectedColumns = [
            'id',
            'customer_name',
            'whatsapp_number',
            'total_price',
            'status',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('orders', $column),
                "Column '{$column}' is missing from orders table."
            );
        }
    }

    /**
     * Test that the order_details table has all required columns.
     */
    public function test_order_details_table_has_expected_columns(): void
    {
        $expectedColumns = [
            'id',
            'order_id',
            'service_id',
            'qty_or_weight',
            'subtotal',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('order_details', $column),
                "Column '{$column}' is missing from order_details table."
            );
        }
    }

    /**
     * Test that DatabaseSeeder executes successfully without errors.
     */
    public function test_database_seeder_executes_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('services', 6);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_details', 4);
    }

    /**
     * Test that DatabaseSeeder populates the default admin account with correct credentials.
     */
    public function test_database_seeder_populates_admin_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'name' => 'Admin Laundry',
            'email' => 'admin@laundry.test',
        ]);

        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('admin123', $admin->password), 'Admin password hash does not match "admin123".');
    }

    /**
     * Test that DatabaseSeeder populates the exact 6 master services.
     */
    public function test_database_seeder_populates_six_master_services(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expectedServices = [
            [
                'service_name' => 'Cuci Komplit Reguler',
                'category' => 'Kiloan',
                'price_per_unit' => 7000,
                'unit_type' => 'Kg',
            ],
            [
                'service_name' => 'Cuci Kering Lipat Cepat',
                'category' => 'Kiloan',
                'price_per_unit' => 5000,
                'unit_type' => 'Kg',
            ],
            [
                'service_name' => 'Cuci Kilat Express 3 Jam',
                'category' => 'Express',
                'price_per_unit' => 15000,
                'unit_type' => 'Kg',
            ],
            [
                'service_name' => 'Bed Cover King Size',
                'category' => 'Satuan',
                'price_per_unit' => 25000,
                'unit_type' => 'Pcs',
            ],
            [
                'service_name' => 'Jas / Blazer Dry Cleaning',
                'category' => 'Satuan',
                'price_per_unit' => 35000,
                'unit_type' => 'Pcs',
            ],
            [
                'service_name' => 'Selimut Tebal Bulu Halus',
                'category' => 'Satuan',
                'price_per_unit' => 20000,
                'unit_type' => 'Pcs',
            ],
        ];

        $this->assertCount(6, Service::all());

        foreach ($expectedServices as $expected) {
            $this->assertDatabaseHas('services', $expected);
        }
    }

    /**
     * Test that DatabaseSeeder populates sample initial orders with accurate details.
     */
    public function test_database_seeder_populates_sample_orders_and_details(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verify Siti Rahmawati's order
        $sitiOrder = Order::with('orderDetails.service')->where('customer_name', 'Siti Rahmawati')->first();
        $this->assertNotNull($sitiOrder);
        $this->assertEquals('081234567890', $sitiOrder->whatsapp_number);
        $this->assertEquals('Dicuci', $sitiOrder->status);
        $this->assertEquals(53000, $sitiOrder->total_price);
        $this->assertCount(2, $sitiOrder->orderDetails);

        $sitiSubtotalSum = $sitiOrder->orderDetails->sum('subtotal');
        $this->assertEquals($sitiOrder->total_price, $sitiSubtotalSum);

        // 2. Verify Budi Santoso's order
        $budiOrder = Order::with('orderDetails.service')->where('customer_name', 'Budi Santoso')->first();
        $this->assertNotNull($budiOrder);
        $this->assertEquals('089876543210', $budiOrder->whatsapp_number);
        $this->assertEquals('Selesai', $budiOrder->status);
        $this->assertEquals(115000, $budiOrder->total_price);
        $this->assertCount(2, $budiOrder->orderDetails);

        $budiSubtotalSum = $budiOrder->orderDetails->sum('subtotal');
        $this->assertEquals($budiOrder->total_price, $budiSubtotalSum);
    }

    /**
     * Test bidirectional relationship between Order and OrderDetail.
     */
    public function test_order_and_order_details_bidirectional_relationship(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertTrue($order->orderDetails()->exists());

        $firstDetail = $order->orderDetails->first();
        $this->assertInstanceOf(OrderDetail::class, $firstDetail);
        $this->assertEquals($order->id, $firstDetail->order->id);
    }

    /**
     * Test bidirectional relationship between Service and OrderDetail.
     */
    public function test_service_and_order_details_bidirectional_relationship(): void
    {
        $this->seed(DatabaseSeeder::class);

        $service = Service::where('service_name', 'Cuci Komplit Reguler')->first();
        $this->assertNotNull($service);
        $this->assertTrue($service->orderDetails()->exists());

        $detail = $service->orderDetails->first();
        $this->assertInstanceOf(OrderDetail::class, $detail);
        $this->assertEquals($service->id, $detail->service->id);
    }

    /**
     * Test foreign key cascade deletion on orders table.
     */
    public function test_cascade_delete_order_removes_details(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = Order::where('customer_name', 'Siti Rahmawati')->first();
        $orderId = $order->id;
        $detailIds = $order->orderDetails->pluck('id')->toArray();

        $this->assertNotEmpty($detailIds);

        $order->delete();

        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
        foreach ($detailIds as $id) {
            $this->assertDatabaseMissing('order_details', ['id' => $id]);
        }
    }

    /**
     * Test foreign key cascade deletion on services table.
     */
    public function test_cascade_delete_service_removes_details(): void
    {
        $this->seed(DatabaseSeeder::class);

        $service = Service::create([
            'service_name' => 'Temp Test Service',
            'category' => 'Satuan',
            'price_per_unit' => 10000,
            'unit_type' => 'Pcs',
        ]);

        $order = Order::create([
            'customer_name' => 'Cascade Test Customer',
            'whatsapp_number' => '080000000000',
            'total_price' => 10000,
            'status' => 'Antrean',
        ]);

        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'qty_or_weight' => 1,
            'subtotal' => 10000,
        ]);

        $detailId = $detail->id;
        $service->delete();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
        $this->assertDatabaseMissing('order_details', ['id' => $detailId]);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    /**
     * Test default status and total_price values for orders.
     */
    public function test_order_model_defaults(): void
    {
        $order = Order::create([
            'customer_name' => 'Default Test User',
            'whatsapp_number' => '081299998888',
        ]);

        $this->assertEquals('Antrean', $order->status);
        $this->assertEquals(0, $order->total_price);
    }

    /**
     * Test that DatabaseSeeder can run multiple times idempotently.
     */
    public function test_database_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(1, User::where('username', 'admin')->count());
        $this->assertEquals(6, Service::count());
        $this->assertEquals(2, Order::count());
        $this->assertEquals(4, OrderDetail::count());
    }
}

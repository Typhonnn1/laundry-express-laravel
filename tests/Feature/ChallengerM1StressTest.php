<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChallengerM1StressTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Suite 1: Data Relationships & Navigation Integrity
    |--------------------------------------------------------------------------
    */

    public function test_bidirectional_relationships_and_eager_loading(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Order -> orderDetails
        $orders = Order::with('orderDetails.service')->get();
        $this->assertGreaterThanOrEqual(2, $orders->count());
        foreach ($orders as $order) {
            $this->assertTrue($order->orderDetails->isNotEmpty());
            foreach ($order->orderDetails as $detail) {
                // 2. OrderDetail -> order
                $this->assertEquals($order->id, $detail->order->id);
                // 3. OrderDetail -> service
                $this->assertNotNull($detail->service);
                $this->assertInstanceOf(Service::class, $detail->service);
            }
        }

        // 4. Service -> orderDetails -> order
        $servicesWithOrders = Service::has('orderDetails')->with('orderDetails.order')->get();
        $this->assertNotEmpty($servicesWithOrders);
        foreach ($servicesWithOrders as $service) {
            foreach ($service->orderDetails as $detail) {
                $this->assertEquals($service->id, $detail->service_id);
                $this->assertNotNull($detail->order);
                $this->assertInstanceOf(Order::class, $detail->order);
            }
        }
    }

    public function test_order_without_details_handles_empty_relations(): void
    {
        $order = Order::create([
            'customer_name' => 'Empty Order Customer',
            'whatsapp_number' => '081122334455',
            'total_price' => 0,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $this->assertTrue($order->orderDetails->isEmpty());
        $this->assertEquals(0, $order->orderDetails()->count());
        $this->assertEquals(0, $order->total_price);
        $order->recalculateTotalPrice();
        $this->assertEquals(0, $order->fresh()->total_price);
    }

    public function test_foreign_key_constraint_prevents_orphan_order_details(): void
    {
        $this->seed(DatabaseSeeder::class);
        $service = Service::first();

        // Invalid order_id must throw QueryException
        $this->expectException(QueryException::class);
        OrderDetail::create([
            'order_id' => 99999999, // non-existent
            'service_id' => $service->id,
            'qty_or_weight' => 2,
            'subtotal' => 14000,
        ]);
    }

    public function test_foreign_key_constraint_prevents_orphan_service_details(): void
    {
        $this->seed(DatabaseSeeder::class);
        $order = Order::first();

        // Invalid service_id must throw QueryException
        $this->expectException(QueryException::class);
        OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => 99999999, // non-existent
            'qty_or_weight' => 2,
            'subtotal' => 14000,
        ]);
    }

    public function test_high_volume_relationship_scaling(): void
    {
        $this->seed(DatabaseSeeder::class);
        $services = Service::all();

        $order = Order::create([
            'customer_name' => 'Bulk Customer',
            'whatsapp_number' => '087711223344',
            'total_price' => 0,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $itemCount = 50;
        $expectedTotal = 0;

        for ($i = 0; $i < $itemCount; $i++) {
            $service = $services[$i % $services->count()];
            $qty = ($i % 5) + 1;
            $subtotal = $qty * $service->price_per_unit;
            $expectedTotal += $subtotal;

            OrderDetail::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'qty_or_weight' => $qty,
                'subtotal' => $subtotal,
            ]);
        }

        $order->refresh();
        $this->assertCount($itemCount, $order->orderDetails);

        $order->recalculateTotalPrice();
        $this->assertEquals($expectedTotal, $order->fresh()->total_price);
    }

    /*
    |--------------------------------------------------------------------------
    | Suite 2: Math Arithmetic & Grand Total Integrity
    |--------------------------------------------------------------------------
    */

    public function test_math_arithmetic_of_seeded_orders(): void
    {
        $this->seed(DatabaseSeeder::class);

        $orders = Order::with('orderDetails.service')->get();
        foreach ($orders as $order) {
            $calculatedTotal = 0;
            foreach ($order->orderDetails as $detail) {
                $expectedSubtotal = $detail->qty_or_weight * $detail->service->price_per_unit;
                $this->assertEquals(
                    $expectedSubtotal,
                    $detail->subtotal,
                    "Subtotal mismatch for detail #{$detail->id}"
                );
                $calculatedTotal += $detail->subtotal;
            }

            $this->assertEquals(
                $calculatedTotal,
                $order->total_price,
                "Grand total mismatch for order #{$order->id} ({$order->customer_name})"
            );
        }
    }

    public function test_large_arithmetic_boundaries_and_formatting(): void
    {
        $service = Service::create([
            'service_name' => 'Industrial Bulk Laundry',
            'category' => Service::CATEGORY_EXPRESS,
            'price_per_unit' => 35000,
            'unit_type' => Service::UNIT_KG,
            'description' => 'Industrial washing service',
        ]);

        $order = Order::create([
            'customer_name' => 'Hotel Mega Bintang',
            'whatsapp_number' => '082199998888',
            'total_price' => 0,
            'status' => Order::STATUS_ANTREAN,
        ]);

        // 50,000 Kg at 35,000 / Kg = 1,750,000,000 (1.75 Billion IDR)
        $qty = 50000;
        $subtotal = $qty * $service->price_per_unit;
        $this->assertEquals(1750000000, $subtotal);

        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'qty_or_weight' => $qty,
            'subtotal' => $subtotal,
        ]);

        $order->recalculateTotalPrice();
        $freshOrder = $order->fresh();

        $this->assertEquals(1750000000, $freshOrder->total_price);
        $this->assertEquals('Rp 1.750.000.000', $freshOrder->formatted_total_price);
        $this->assertEquals('Rp 1.750.000.000', $detail->formatted_subtotal);
        $this->assertEquals('Rp 35.000 / Kg', $service->formatted_rate);
    }

    public function test_recalculate_total_price_dynamically_adjusts_on_mutations(): void
    {
        $service1 = Service::create([
            'service_name' => 'Service A',
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
        ]);
        $service2 = Service::create([
            'service_name' => 'Service B',
            'category' => 'Satuan',
            'price_per_unit' => 20000,
            'unit_type' => 'Pcs',
        ]);

        $order = Order::create([
            'customer_name' => 'Mutation Test',
            'whatsapp_number' => '081233445566',
        ]);

        $detail1 = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service1->id,
            'qty_or_weight' => 2,
            'subtotal' => 20000,
        ]);
        $order->recalculateTotalPrice();
        $this->assertEquals(20000, $order->fresh()->total_price);

        $detail2 = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service2->id,
            'qty_or_weight' => 3,
            'subtotal' => 60000,
        ]);
        $order->recalculateTotalPrice();
        $this->assertEquals(80000, $order->fresh()->total_price);

        // Delete one detail and recalculate
        $detail1->delete();
        $order->recalculateTotalPrice();
        $this->assertEquals(60000, $order->fresh()->total_price);

        // Delete remaining detail and recalculate
        $detail2->delete();
        $order->recalculateTotalPrice();
        $this->assertEquals(0, $order->fresh()->total_price);
    }

    /*
    |--------------------------------------------------------------------------
    | Suite 3: Password Hashing Security & User Attributes
    |--------------------------------------------------------------------------
    */

    public function test_password_hashing_security_and_verification(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);

        // Valid password check
        $this->assertTrue(Hash::check('admin123', $admin->password));

        // Invalid password attempts must fail
        $invalidPasswords = [
            'admin124',
            'Admin123',
            'ADMIN123',
            'admin 123',
            'admin123 ',
            ' ',
            '',
            'password',
            'secret',
            "' OR '1'='1",
            "admin123'; DROP TABLE users; --",
            '<script>alert("xss")</script>',
        ];

        foreach ($invalidPasswords as $invalidPassword) {
            $this->assertFalse(
                Hash::check($invalidPassword, $admin->password),
                "Hash check unexpectedly succeeded for invalid password: '{$invalidPassword}'"
            );
        }

        // Empirical observation: Native bcrypt truncates at null byte \0 due to C-string termination
        $nullByteInput = "admin123\0extra_payload";
        $this->assertTrue(
            Hash::check($nullByteInput, $admin->password),
            'Native PHP bcrypt crypt_blowfish truncates at null byte termination.'
        );

        // Verify hash algorithm is bcrypt ($2y$)
        $this->assertStringStartsWith('$2y$', $admin->password);
        $this->assertFalse(Hash::needsRehash($admin->password));
    }

    public function test_user_model_hides_sensitive_attributes_on_serialization(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('username', 'admin')->first();

        $arrayData = $admin->toArray();
        $this->assertArrayNotHasKey('password', $arrayData);
        $this->assertArrayNotHasKey('remember_token', $arrayData);

        $jsonData = json_encode($admin);
        $this->assertStringNotContainsString('password', $jsonData);
        $this->assertStringNotContainsString('remember_token', $jsonData);
        $this->assertStringNotContainsString('admin123', $jsonData);
    }

    public function test_user_password_cast_hashes_plain_text_on_creation(): void
    {
        $newUser = User::create([
            'name' => 'Operator Baru',
            'username' => 'operator',
            'email' => 'operator@laundry.test',
            'password' => 'secretPass999',
        ]);

        $this->assertNotEquals('secretPass999', $newUser->password);
        $this->assertStringStartsWith('$2y$', $newUser->password);
        $this->assertTrue(Hash::check('secretPass999', $newUser->password));
        $this->assertFalse(Hash::check('wrongPass', $newUser->password));
    }

    public function test_user_username_uniqueness_enforced(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->expectException(QueryException::class);
        User::create([
            'name' => 'Duplicate Admin',
            'username' => 'admin', // already exists
            'email' => 'other@laundry.test',
            'password' => 'password123',
        ]);
    }

    public function test_user_email_uniqueness_enforced(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->expectException(QueryException::class);
        User::create([
            'name' => 'Duplicate Email',
            'username' => 'admin2',
            'email' => 'admin@laundry.test', // already exists
            'password' => 'password123',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Suite 4: Cascade Operations & Transactional Integrity
    |--------------------------------------------------------------------------
    */

    public function test_cascade_delete_order_removes_all_child_details(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = Order::where('customer_name', 'Siti Rahmawati')->first();
        $this->assertNotNull($order);
        $orderId = $order->id;

        $detailIds = $order->orderDetails->pluck('id')->toArray();
        $this->assertCount(2, $detailIds);

        // Delete order
        $order->delete();

        // Order and all details must be gone
        $this->assertDatabaseMissing('orders', ['id' => $orderId]);
        foreach ($detailIds as $detailId) {
            $this->assertDatabaseMissing('order_details', ['id' => $detailId]);
        }
    }

    public function test_cascade_delete_service_removes_details_but_preserves_orders(): void
    {
        $this->seed(DatabaseSeeder::class);

        $service = Service::where('service_name', 'Bed Cover King Size')->first();
        $serviceId = $service->id;

        $affectedDetails = OrderDetail::where('service_id', $serviceId)->get();
        $this->assertNotEmpty($affectedDetails);
        $detailIds = $affectedDetails->pluck('id')->toArray();
        $parentOrderIds = $affectedDetails->pluck('order_id')->unique()->toArray();

        // Delete service
        $service->delete();

        // Service and its details are deleted
        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
        foreach ($detailIds as $detailId) {
            $this->assertDatabaseMissing('order_details', ['id' => $detailId]);
        }

        // Parent orders MUST still exist
        foreach ($parentOrderIds as $orderId) {
            $this->assertDatabaseHas('orders', ['id' => $orderId]);
        }
    }

    public function test_deleting_detail_preserves_order_and_service(): void
    {
        $this->seed(DatabaseSeeder::class);

        $detail = OrderDetail::first();
        $orderId = $detail->order_id;
        $serviceId = $detail->service_id;
        $detailId = $detail->id;

        $detail->delete();

        $this->assertDatabaseMissing('order_details', ['id' => $detailId]);
        $this->assertDatabaseHas('orders', ['id' => $orderId]);
        $this->assertDatabaseHas('services', ['id' => $serviceId]);
    }

    public function test_transaction_rollback_guarantees_no_orphaned_records(): void
    {
        $this->seed(DatabaseSeeder::class);
        $service = Service::first();

        $initialOrderCount = Order::count();
        $initialDetailCount = OrderDetail::count();

        try {
            DB::transaction(function () use ($service) {
                $order = Order::create([
                    'customer_name' => 'Failing Transaction Customer',
                    'whatsapp_number' => '089900112233',
                    'total_price' => 50000,
                ]);

                OrderDetail::create([
                    'order_id' => $order->id,
                    'service_id' => $service->id,
                    'qty_or_weight' => 2,
                    'subtotal' => 14000,
                ]);

                // Simulate intentional exception inside transaction
                throw new \RuntimeException('Simulated payment / network failure');
            });
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        $this->assertEquals($initialOrderCount, Order::count());
        $this->assertEquals($initialDetailCount, OrderDetail::count());
        $this->assertDatabaseMissing('orders', ['customer_name' => 'Failing Transaction Customer']);
    }

    /*
    |--------------------------------------------------------------------------
    | Suite 5: Model Constraints, Defaults, and Progress Mutators
    |--------------------------------------------------------------------------
    */

    public function test_status_badge_classes_and_progress_percentage(): void
    {
        $order = new Order;

        $order->status = Order::STATUS_ANTREAN;
        $this->assertEquals('warning text-dark', $order->status_badge_class);
        $this->assertEquals(33, $order->progress_percentage);

        $order->status = Order::STATUS_DICUCI;
        $this->assertEquals('info text-dark', $order->status_badge_class);
        $this->assertEquals(66, $order->progress_percentage);

        $order->status = Order::STATUS_SELESAI;
        $this->assertEquals('success', $order->status_badge_class);
        $this->assertEquals(100, $order->progress_percentage);

        $order->status = 'Unknown';
        $this->assertEquals('secondary', $order->status_badge_class);
        $this->assertEquals(0, $order->progress_percentage);
    }
}

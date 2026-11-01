<?php

namespace Tests\Feature\Adversarial;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Milestone 1 Adversarial Hardening & Stress Test Suite (Tier 5)
 *
 * Empirically stress-tests:
 * 1. Unique constraints on users (username, email) and nullability
 * 2. Foreign key cascade deletions across orders, services, and order_details
 * 3. Database seeder idempotency across multiple consecutive executions
 * 4. Boundary values, field lengths, enums, and data integrity
 */
class Milestone1StressTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // 1. UNIQUE CONSTRAINTS & NULLABILITY ON USERS
    // ==========================================

    public function test_duplicate_username_throws_query_exception(): void
    {
        User::create([
            'name' => 'Original User',
            'username' => 'unique_user_alpha',
            'email' => 'user_alpha@laundry.test',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(QueryException::class);

        // Attempt to insert duplicate username with different email
        User::create([
            'name' => 'Duplicate Username User',
            'username' => 'unique_user_alpha',
            'email' => 'user_beta@laundry.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_duplicate_email_throws_query_exception(): void
    {
        User::create([
            'name' => 'Original Email User',
            'username' => 'user_unique_1',
            'email' => 'shared_email@laundry.test',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(QueryException::class);

        // Attempt to insert duplicate email with different username
        User::create([
            'name' => 'Duplicate Email User',
            'username' => 'user_unique_2',
            'email' => 'shared_email@laundry.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_null_username_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);

        User::create([
            'name' => 'No Username User',
            'username' => null,
            'email' => 'no_user@laundry.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_null_email_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);

        User::create([
            'name' => 'No Email User',
            'username' => 'no_email_user',
            'email' => null,
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_null_password_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);

        User::create([
            'name' => 'No Password User',
            'username' => 'no_pass_user',
            'email' => 'nopass@laundry.test',
            'password' => null,
        ]);
    }

    // ==========================================
    // 2. FOREIGN KEY CONSTRAINTS & CASCADE PROPAGATION
    // ==========================================

    public function test_cascade_delete_order_removes_all_its_order_details(): void
    {
        $service = Service::create([
            'service_name' => 'Cascade Test Service A',
            'category' => Service::CATEGORY_KILOAN,
            'price_per_unit' => 6000,
            'unit_type' => Service::UNIT_KG,
        ]);

        $order = Order::create([
            'customer_name' => 'Cascade Target Customer',
            'whatsapp_number' => '081111111111',
            'total_price' => 18000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $detail1 = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'qty_or_weight' => 2,
            'subtotal' => 12000,
        ]);

        $detail2 = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'qty_or_weight' => 1,
            'subtotal' => 6000,
        ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_details', ['id' => $detail1->id]);
        $this->assertDatabaseHas('order_details', ['id' => $detail2->id]);

        // Delete parent order
        $order->delete();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_details', ['id' => $detail1->id]);
        $this->assertDatabaseMissing('order_details', ['id' => $detail2->id]);
        // The service should still exist
        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_cascade_delete_service_removes_only_its_details_leaving_orders(): void
    {
        $serviceTarget = Service::create([
            'service_name' => 'Target To Delete',
            'category' => Service::CATEGORY_SATUAN,
            'price_per_unit' => 10000,
            'unit_type' => Service::UNIT_PCS,
        ]);

        $serviceKeep = Service::create([
            'service_name' => 'Service To Keep',
            'category' => Service::CATEGORY_SATUAN,
            'price_per_unit' => 20000,
            'unit_type' => Service::UNIT_PCS,
        ]);

        $order = Order::create([
            'customer_name' => 'Dual Detail Customer',
            'whatsapp_number' => '082222222222',
            'total_price' => 30000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $detailTarget = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $serviceTarget->id,
            'qty_or_weight' => 1,
            'subtotal' => 10000,
        ]);

        $detailKeep = OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $serviceKeep->id,
            'qty_or_weight' => 1,
            'subtotal' => 20000,
        ]);

        // Delete the first service
        $serviceTarget->delete();

        $this->assertDatabaseMissing('services', ['id' => $serviceTarget->id]);
        $this->assertDatabaseMissing('order_details', ['id' => $detailTarget->id]);
        // The order must remain
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        // The second service and its detail must remain
        $this->assertDatabaseHas('services', ['id' => $serviceKeep->id]);
        $this->assertDatabaseHas('order_details', ['id' => $detailKeep->id]);
    }

    public function test_foreign_key_constraint_prevents_orphan_order_details(): void
    {
        $service = Service::create([
            'service_name' => 'Valid Service',
            'category' => Service::CATEGORY_KILOAN,
            'price_per_unit' => 7000,
            'unit_type' => Service::UNIT_KG,
        ]);

        $this->expectException(QueryException::class);

        // Attempt to insert order detail with non-existent order_id 999999
        OrderDetail::create([
            'order_id' => 999999,
            'service_id' => $service->id,
            'qty_or_weight' => 1,
            'subtotal' => 7000,
        ]);
    }

    public function test_foreign_key_constraint_prevents_invalid_service_id(): void
    {
        $order = Order::create([
            'customer_name' => 'Valid Order Customer',
            'whatsapp_number' => '083333333333',
            'total_price' => 7000,
            'status' => Order::STATUS_ANTREAN,
        ]);

        $this->expectException(QueryException::class);

        // Attempt to insert order detail with non-existent service_id 999999
        OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => 999999,
            'qty_or_weight' => 1,
            'subtotal' => 7000,
        ]);
    }

    // ==========================================
    // 3. SEEDER IDEMPOTENCY STRESS HARNESS
    // ==========================================

    public function test_seeder_five_consecutive_runs_maintain_exact_data_state(): void
    {
        // Execute seeder 5 times in succession
        for ($i = 1; $i <= 5; $i++) {
            $this->seed(DatabaseSeeder::class);
        }

        // 1. Exactly 1 admin user
        $this->assertDatabaseCount('users', 1);
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('Admin Laundry', $admin->name);
        $this->assertEquals('admin@laundry.test', $admin->email);
        $this->assertTrue(Hash::check('admin123', $admin->password));

        // 2. Exactly 6 services
        $this->assertDatabaseCount('services', 6);
        $this->assertCount(6, Service::all());

        // 3. Exactly 2 sample orders
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_details', 4);

        $siti = Order::where('customer_name', 'Siti Rahmawati')->first();
        $this->assertNotNull($siti);
        $this->assertEquals('081234567890', $siti->whatsapp_number);
        $this->assertEquals(Order::STATUS_DICUCI, $siti->status);
        $this->assertEquals(53000, $siti->total_price);
        $this->assertEquals(2, $siti->orderDetails()->count());
        $this->assertEquals(53000, $siti->orderDetails()->sum('subtotal'));

        $budi = Order::where('customer_name', 'Budi Santoso')->first();
        $this->assertNotNull($budi);
        $this->assertEquals('089876543210', $budi->whatsapp_number);
        $this->assertEquals(Order::STATUS_SELESAI, $budi->status);
        $this->assertEquals(115000, $budi->total_price);
        $this->assertEquals(2, $budi->orderDetails()->count());
        $this->assertEquals(115000, $budi->orderDetails()->sum('subtotal'));
    }

    // ==========================================
    // 4. BOUNDARY VALUES, NULLABILITY & ENUMS
    // ==========================================

    public function test_service_description_can_be_null(): void
    {
        $service = Service::create([
            'service_name' => 'Service Without Description',
            'category' => Service::CATEGORY_SATUAN,
            'price_per_unit' => 12000,
            'unit_type' => Service::UNIT_PCS,
            'description' => null,
        ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'description' => null,
        ]);
        $this->assertNull($service->fresh()->description);
    }

    public function test_service_name_maximum_boundary_100_characters(): void
    {
        $exact100CharName = str_repeat('A', 100);

        $service = Service::create([
            'service_name' => $exact100CharName,
            'category' => Service::CATEGORY_EXPRESS,
            'price_per_unit' => 25000,
            'unit_type' => Service::UNIT_KG,
        ]);

        $this->assertEquals($exact100CharName, $service->fresh()->service_name);
        $this->assertEquals(100, strlen($service->fresh()->service_name));
    }

    public function test_order_customer_name_maximum_boundary_100_characters(): void
    {
        $exact100CharCustomer = str_repeat('B', 100);

        $order = Order::create([
            'customer_name' => $exact100CharCustomer,
            'whatsapp_number' => '081234567890',
        ]);

        $this->assertEquals($exact100CharCustomer, $order->fresh()->customer_name);
    }

    public function test_order_whatsapp_maximum_boundary_20_characters(): void
    {
        $exact20Char = '+6281234567890123456'; // Exactly 20 characters: '+' (1) + 19 digits = 20

        $order = Order::create([
            'customer_name' => 'Boundary Customer',
            'whatsapp_number' => $exact20Char,
        ]);

        $this->assertEquals($exact20Char, $order->fresh()->whatsapp_number);
        $this->assertEquals(20, strlen($order->fresh()->whatsapp_number));
    }

    public function test_order_model_defaults_when_attributes_omitted(): void
    {
        $order = Order::create([
            'customer_name' => 'Defaults Tester',
            'whatsapp_number' => '085555555555',
        ]);

        $this->assertEquals(Order::STATUS_ANTREAN, $order->fresh()->status);
        $this->assertEquals(0, $order->fresh()->total_price);
        $this->assertEquals(33, $order->fresh()->progress_percentage);
        $this->assertEquals('warning text-dark', $order->fresh()->status_badge_class);
    }

    public function test_recalculate_total_price_aggregates_subtotals(): void
    {
        $service = Service::create([
            'service_name' => 'Recalc Service',
            'category' => Service::CATEGORY_KILOAN,
            'price_per_unit' => 8000,
            'unit_type' => Service::UNIT_KG,
        ]);

        $order = Order::create([
            'customer_name' => 'Recalc Customer',
            'whatsapp_number' => '086666666666',
            'total_price' => 0,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'qty_or_weight' => 3,
            'subtotal' => 24000,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'qty_or_weight' => 2,
            'subtotal' => 16000,
        ]);

        $order->recalculateTotalPrice();

        $this->assertEquals(40000, $order->fresh()->total_price);
        $this->assertEquals('Rp 40.000', $order->fresh()->formatted_total_price);
    }
}

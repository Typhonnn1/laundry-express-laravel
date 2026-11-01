<?php

namespace Tests\Feature\Adversarial;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Live MySQL Database Stress Test Suite
 *
 * Runs directly against the live MySQL instance (db_laundry_express)
 * to empirically verify InnoDB foreign keys, strict SQL mode, and unique constraints.
 */
class MySQLDatabaseStressTest extends TestCase
{
    protected string $conn = 'mysql';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql.database' => 'db_laundry_express']);
        DB::purge('mysql');

        // Ensure MySQL connection is active
        try {
            DB::connection($this->conn)->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL connection not available: '.$e->getMessage());
        }
    }

    public function test_mysql_unique_username_constraint_enforced(): void
    {
        $this->expectException(QueryException::class);

        // 'admin' username already seeded in MySQL
        User::on($this->conn)->create([
            'name' => 'Admin Impersonator',
            'username' => 'admin',
            'email' => 'impersonator@laundry.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_mysql_unique_email_constraint_enforced(): void
    {
        $this->expectException(QueryException::class);

        // 'admin@laundry.test' email already seeded in MySQL
        User::on($this->conn)->create([
            'name' => 'Email Collision',
            'username' => 'new_unique_user',
            'email' => 'admin@laundry.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_mysql_foreign_key_cascade_order_delete(): void
    {
        DB::connection($this->conn)->beginTransaction();

        try {
            $service = Service::on($this->conn)->first();

            $order = Order::on($this->conn)->create([
                'customer_name' => 'MySQL Cascade Order Test',
                'whatsapp_number' => '087777777777',
                'total_price' => 15000,
                'status' => 'Antrean',
            ]);

            $detail = OrderDetail::on($this->conn)->create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'qty_or_weight' => 1,
                'subtotal' => 15000,
            ]);

            $this->assertNotNull(OrderDetail::on($this->conn)->find($detail->id));

            // Delete the parent order
            $order->delete();

            // Detail must be removed by InnoDB cascade
            $this->assertNull(OrderDetail::on($this->conn)->find($detail->id));
        } finally {
            DB::connection($this->conn)->rollBack();
        }
    }

    public function test_mysql_foreign_key_cascade_service_delete(): void
    {
        DB::connection($this->conn)->beginTransaction();

        try {
            $service = Service::on($this->conn)->create([
                'service_name' => 'MySQL Cascade Svc Test',
                'category' => 'Satuan',
                'price_per_unit' => 20000,
                'unit_type' => 'Pcs',
            ]);

            $order = Order::on($this->conn)->create([
                'customer_name' => 'MySQL Cascade Customer',
                'whatsapp_number' => '088888888888',
                'total_price' => 20000,
                'status' => 'Antrean',
            ]);

            $detail = OrderDetail::on($this->conn)->create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'qty_or_weight' => 1,
                'subtotal' => 20000,
            ]);

            $this->assertNotNull(OrderDetail::on($this->conn)->find($detail->id));

            // Delete the service
            $service->delete();

            // Detail must be cascade deleted
            $this->assertNull(OrderDetail::on($this->conn)->find($detail->id));
            // Order must remain
            $this->assertNotNull(Order::on($this->conn)->find($order->id));
        } finally {
            DB::connection($this->conn)->rollBack();
        }
    }

    public function test_mysql_foreign_key_rejects_orphan_order_details(): void
    {
        $this->expectException(QueryException::class);

        $service = Service::on($this->conn)->first();

        OrderDetail::on($this->conn)->create([
            'order_id' => 99999999, // non-existent
            'service_id' => $service->id,
            'qty_or_weight' => 1,
            'subtotal' => 10000,
        ]);
    }

    public function test_mysql_strict_mode_rejects_invalid_enum_for_category(): void
    {
        $this->expectException(QueryException::class);

        Service::on($this->conn)->create([
            'service_name' => 'Illegal Enum Service',
            'category' => 'NonExistentCategory', // Invalid enum
            'price_per_unit' => 10000,
            'unit_type' => 'Kg',
        ]);
    }

    public function test_mysql_strict_mode_rejects_invalid_enum_for_unit_type(): void
    {
        $this->expectException(QueryException::class);

        Service::on($this->conn)->create([
            'service_name' => 'Illegal Unit Service',
            'category' => 'Kiloan',
            'price_per_unit' => 10000,
            'unit_type' => 'Litres', // Invalid enum
        ]);
    }

    public function test_mysql_strict_mode_rejects_invalid_enum_for_order_status(): void
    {
        $this->expectException(QueryException::class);

        Order::on($this->conn)->create([
            'customer_name' => 'Invalid Status Customer',
            'whatsapp_number' => '089999999999',
            'status' => 'Cancelled', // Invalid enum
        ]);
    }
}

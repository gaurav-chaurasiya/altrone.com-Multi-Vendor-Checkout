<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    protected $customer;
    protected $admin;
    protected $vendor1;
    protected $vendor2;
    protected $product1;
    protected $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role' => 'customer'
        ]);

        $this->admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        $this->vendor1 = Vendor::create(['name' => 'Vendor 1', 'email' => 'v1@test.com']);
        $this->vendor2 = Vendor::create(['name' => 'Vendor 2', 'email' => 'v2@test.com']);

        $this->product1 = Product::create([
            'vendor_id' => $this->vendor1->id,
            'name' => 'P1',
            'price' => 100,
            'stock' => 10
        ]);

        $this->product2 = Product::create([
            'vendor_id' => $this->vendor2->id,
            'name' => 'P2',
            'price' => 200,
            'stock' => 5
        ]);
    }

    public function test_customer_can_add_item_to_cart()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/cart', [
            'product_id' => $this->product1->id,
            'quantity' => 2
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product added to cart.');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product1->id,
            'quantity' => 2
        ]);
    }

    public function test_checkout_splits_orders_by_vendor()
    {
        Sanctum::actingAs($this->customer);

        // Add items from different vendors
        $this->postJson('/api/cart', ['product_id' => $this->product1->id, 'quantity' => 1])->assertStatus(200);
        $this->postJson('/api/cart', ['product_id' => $this->product2->id, 'quantity' => 1])->assertStatus(200);

        $initialOrderCount = \App\Models\Order::count();
        $response = $this->postJson('/api/checkout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Checkout successful. Orders created.');

        // Verify 2 new orders
        $this->assertEquals($initialOrderCount + 2, \App\Models\Order::count());
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor1->id,
            'total_amount' => 100
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor2->id,
            'total_amount' => 200
        ]);

        // Verify stock deduction
        $this->assertEquals(9, $this->product1->fresh()->stock);
        $this->assertEquals(4, $this->product2->fresh()->stock);

        // Verify cart cleared
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cannot_checkout_with_insufficient_stock()
    {
        Sanctum::actingAs($this->customer);

        // Add 1 item
        $this->postJson('/api/cart', ['product_id' => $this->product1->id, 'quantity' => 1])->assertStatus(200);
        $this->customer->refresh();

        // Manually reduce stock to 0 in DB
        $this->product1->update(['stock' => 0]);

        $initialOrderCount = \App\Models\Order::count();
        $response = $this->postJson('/api/checkout');

        $response->assertStatus(422)
            ->assertJsonPath('message', "Insufficient stock for product: {$this->product1->name}");

        // Verify no new orders created
        $this->assertEquals($initialOrderCount, \App\Models\Order::count());
    }

    public function test_admin_can_view_all_orders()
    {
        // Create an order first
        Sanctum::actingAs($this->customer);
        $this->postJson('/api/cart', ['product_id' => $this->product1->id, 'quantity' => 1]);
        $this->postJson('/api/checkout');

        // Try as customer (should fail access to admin route or restricted by policy)
        Sanctum::actingAs($this->customer);
        $response = $this->getJson('/api/admin/orders');
        $response->assertStatus(403);

        // Try as admin
        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/admin/orders');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }
}

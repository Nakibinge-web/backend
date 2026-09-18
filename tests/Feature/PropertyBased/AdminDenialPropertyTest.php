<?php

namespace Tests\Feature\PropertyBased;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\SalePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Admin Denial Rules
 * 
 * These tests verify that admins are properly denied access to all non-user resources:
 * - Product, Category, Supplier, Purchase, Sale, StockMovement (Property 6)
 * 
 * Each property is tested with 100 iterations using random data.
 */
class AdminDenialPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Role $adminRole;
    private Category $category;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'domain' => 'test.example.com',
        ]);
        
        // Create admin role
        $this->adminRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'admin',
            'description' => 'Admin role',
        ]);
        
        // Create category and supplier for products
        $this->category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Category',
        ]);
        
        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
        ]);
    }

    /**
     * Feature: role-based-policy-system, Property 6: Admin Non-User Resource Denial
     * 
     * For any authenticated user with the admin role and any non-user resource
     * (Product, Category, Supplier, Purchase, Sale, StockMovement), all policy
     * methods (viewAny, view, create, update, delete) should return false.
     * 
     * Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6
     * 
     * @test
     */
    public function property_6_admin_cannot_access_non_user_resources()
    {
        // Test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create an admin user
            $admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
            $admin->roles()->attach($this->adminRole);
            
            // Test Product access denial
            $this->assertProductDenial($admin, $i);
            
            // Test Category access denial
            $this->assertCategoryDenial($admin, $i);
            
            // Test Supplier access denial
            $this->assertSupplierDenial($admin, $i);
            
            // Test Purchase access denial
            $this->assertPurchaseDenial($admin, $i);
            
            // Test Sale access denial
            $this->assertSaleDenial($admin, $i);
            
            // Test StockMovement access denial
            $this->assertStockMovementDenial($admin, $i);
        }
    }

    /**
     * Assert that admin is denied all Product operations
     */
    private function assertProductDenial(User $admin, int $iteration): void
    {
        $policy = new ProductPolicy();
        
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Product {$iteration}",
            'sku' => "SKU-{$iteration}",
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'stock' => rand(0, 100),
            'cost_price' => rand(50, 500) / 10,
            'price' => rand(100, 1000) / 10,
        ]);
        
        $this->assertFalse($policy->viewAny($admin), "Iteration {$iteration}: Admin should not view any products");
        $this->assertFalse($policy->view($admin, $product), "Iteration {$iteration}: Admin should not view product");
        $this->assertFalse($policy->create($admin), "Iteration {$iteration}: Admin should not create products");
        $this->assertFalse($policy->update($admin, $product), "Iteration {$iteration}: Admin should not update product");
        $this->assertFalse($policy->delete($admin, $product), "Iteration {$iteration}: Admin should not delete product");
    }

    /**
     * Assert that admin is denied all Category operations
     */
    private function assertCategoryDenial(User $admin, int $iteration): void
    {
        $policy = new CategoryPolicy();
        
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Category {$iteration}",
        ]);
        
        $this->assertFalse($policy->viewAny($admin), "Iteration {$iteration}: Admin should not view any categories");
        $this->assertFalse($policy->view($admin, $category), "Iteration {$iteration}: Admin should not view category");
        $this->assertFalse($policy->create($admin), "Iteration {$iteration}: Admin should not create categories");
        $this->assertFalse($policy->update($admin, $category), "Iteration {$iteration}: Admin should not update category");
        $this->assertFalse($policy->delete($admin, $category), "Iteration {$iteration}: Admin should not delete category");
    }

    /**
     * Assert that admin is denied all Supplier operations
     */
    private function assertSupplierDenial(User $admin, int $iteration): void
    {
        $policy = new SupplierPolicy();
        
        $supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Supplier {$iteration}",
            'email' => "supplier{$iteration}@test.com",
        ]);
        
        $this->assertFalse($policy->viewAny($admin), "Iteration {$iteration}: Admin should not view any suppliers");
        $this->assertFalse($policy->view($admin, $supplier), "Iteration {$iteration}: Admin should not view supplier");
        $this->assertFalse($policy->create($admin), "Iteration {$iteration}: Admin should not create suppliers");
        $this->assertFalse($policy->update($admin, $supplier), "Iteration {$iteration}: Admin should not update supplier");
        $this->assertFalse($policy->delete($admin, $supplier), "Iteration {$iteration}: Admin should not delete supplier");
    }

    /**
     * Assert that admin is denied all Purchase operations
     */
    private function assertPurchaseDenial(User $admin, int $iteration): void
    {
        $policy = new PurchasePolicy();
        
        $purchase = Purchase::create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'total_amount' => rand(100, 10000) / 100,
            'purchase_date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertFalse($policy->viewAny($admin), "Iteration {$iteration}: Admin should not view any purchases");
        $this->assertFalse($policy->view($admin, $purchase), "Iteration {$iteration}: Admin should not view purchase");
        $this->assertFalse($policy->create($admin), "Iteration {$iteration}: Admin should not create purchases");
        $this->assertFalse($policy->update($admin, $purchase), "Iteration {$iteration}: Admin should not update purchase");
        $this->assertFalse($policy->delete($admin, $purchase), "Iteration {$iteration}: Admin should not delete purchase");
    }

    /**
     * Assert that admin is denied all Sale operations
     */
    private function assertSaleDenial(User $admin, int $iteration): void
    {
        $policy = new SalePolicy();
        
        $sale = Sale::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $admin->id,
            'total_amount' => rand(100, 10000) / 100,
            'payment_method' => $this->randomPaymentMethod(),
            'sale_date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertFalse($policy->viewAny($admin), "Iteration {$iteration}: Admin should not view any sales");
        $this->assertFalse($policy->view($admin, $sale), "Iteration {$iteration}: Admin should not view sale");
        $this->assertFalse($policy->create($admin), "Iteration {$iteration}: Admin should not create sales");
        $this->assertFalse($policy->update($admin, $sale), "Iteration {$iteration}: Admin should not update sale");
        $this->assertFalse($policy->delete($admin, $sale), "Iteration {$iteration}: Admin should not delete sale");
    }

    /**
     * Assert that admin is denied all StockMovement operations
     */
    private function assertStockMovementDenial(User $admin, int $iteration): void
    {
        $policy = new StockMovementPolicy();
        
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Product for Stock {$iteration}",
            'sku' => "SKU-STOCK-{$iteration}",
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'stock' => rand(0, 100),
            'cost_price' => rand(50, 500) / 10,
            'price' => rand(100, 1000) / 10,
        ]);
        
        $stockMovement = StockMovement::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'quantity' => rand(1, 50),
            'type' => $this->randomMovementType(),
            'date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertFalse($policy->viewAny($admin), "Iteration {$iteration}: Admin should not view any stock movements");
        $this->assertFalse($policy->view($admin, $stockMovement), "Iteration {$iteration}: Admin should not view stock movement");
        $this->assertFalse($policy->create($admin), "Iteration {$iteration}: Admin should not create stock movements");
        $this->assertFalse($policy->update($admin, $stockMovement), "Iteration {$iteration}: Admin should not update stock movement");
        $this->assertFalse($policy->delete($admin, $stockMovement), "Iteration {$iteration}: Admin should not delete stock movement");
    }

    /**
     * Generate a random payment method for sales
     */
    private function randomPaymentMethod(): string
    {
        $methods = ['cash', 'card', 'transfer', 'mobile'];
        return $methods[array_rand($methods)];
    }

    /**
     * Generate a random movement type for stock movements
     */
    private function randomMovementType(): string
    {
        $types = ['IN', 'OUT'];
        return $types[array_rand($types)];
    }
}

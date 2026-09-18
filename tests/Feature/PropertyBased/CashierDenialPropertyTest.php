<?php

namespace Tests\Feature\PropertyBased;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Cashier Denial Rules
 * 
 * These tests verify that cashiers are properly denied access to all non-sale resources:
 * - Product, Category, Supplier, Purchase, StockMovement, User (Property 9)
 * 
 * Each property is tested with 100 iterations using random data.
 */
class CashierDenialPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Role $cashierRole;
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
        
        // Create cashier role
        $this->cashierRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'cashier',
            'description' => 'Cashier role',
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
     * Feature: role-based-policy-system, Property 9: Cashier Non-Sale Resource Denial
     * 
     * For any authenticated user with the cashier role and any non-sale resource
     * (Product, Category, Supplier, Purchase, StockMovement, User), all policy
     * methods (viewAny, view, create, update, delete) should return false.
     * 
     * Validates: Requirements 7.3, 7.4, 7.5, 7.6, 7.7, 7.8
     * 
     * @test
     */
    public function property_9_cashier_cannot_access_non_sale_resources()
    {
        // Test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create a cashier user
            $cashier = User::factory()->create(['tenant_id' => $this->tenant->id]);
            $cashier->roles()->attach($this->cashierRole);
            
            // Test Product access denial
            $this->assertProductDenial($cashier, $i);
            
            // Test Category access denial
            $this->assertCategoryDenial($cashier, $i);
            
            // Test Supplier access denial
            $this->assertSupplierDenial($cashier, $i);
            
            // Test Purchase access denial
            $this->assertPurchaseDenial($cashier, $i);
            
            // Test StockMovement access denial
            $this->assertStockMovementDenial($cashier, $i);
            
            // Test User access denial
            $this->assertUserDenial($cashier, $i);
        }
    }

    /**
     * Assert that cashier is denied all Product operations
     */
    private function assertProductDenial(User $cashier, int $iteration): void
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
        
        $this->assertFalse($policy->viewAny($cashier), "Iteration {$iteration}: Cashier should not view any products");
        $this->assertFalse($policy->view($cashier, $product), "Iteration {$iteration}: Cashier should not view product");
        $this->assertFalse($policy->create($cashier), "Iteration {$iteration}: Cashier should not create products");
        $this->assertFalse($policy->update($cashier, $product), "Iteration {$iteration}: Cashier should not update product");
        $this->assertFalse($policy->delete($cashier, $product), "Iteration {$iteration}: Cashier should not delete product");
    }

    /**
     * Assert that cashier is denied all Category operations
     */
    private function assertCategoryDenial(User $cashier, int $iteration): void
    {
        $policy = new CategoryPolicy();
        
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Category {$iteration}",
        ]);
        
        $this->assertFalse($policy->viewAny($cashier), "Iteration {$iteration}: Cashier should not view any categories");
        $this->assertFalse($policy->view($cashier, $category), "Iteration {$iteration}: Cashier should not view category");
        $this->assertFalse($policy->create($cashier), "Iteration {$iteration}: Cashier should not create categories");
        $this->assertFalse($policy->update($cashier, $category), "Iteration {$iteration}: Cashier should not update category");
        $this->assertFalse($policy->delete($cashier, $category), "Iteration {$iteration}: Cashier should not delete category");
    }

    /**
     * Assert that cashier is denied all Supplier operations
     */
    private function assertSupplierDenial(User $cashier, int $iteration): void
    {
        $policy = new SupplierPolicy();
        
        $supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Supplier {$iteration}",
            'email' => "supplier{$iteration}@test.com",
        ]);
        
        $this->assertFalse($policy->viewAny($cashier), "Iteration {$iteration}: Cashier should not view any suppliers");
        $this->assertFalse($policy->view($cashier, $supplier), "Iteration {$iteration}: Cashier should not view supplier");
        $this->assertFalse($policy->create($cashier), "Iteration {$iteration}: Cashier should not create suppliers");
        $this->assertFalse($policy->update($cashier, $supplier), "Iteration {$iteration}: Cashier should not update supplier");
        $this->assertFalse($policy->delete($cashier, $supplier), "Iteration {$iteration}: Cashier should not delete supplier");
    }

    /**
     * Assert that cashier is denied all Purchase operations
     */
    private function assertPurchaseDenial(User $cashier, int $iteration): void
    {
        $policy = new PurchasePolicy();
        
        $purchase = Purchase::create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'total_amount' => rand(100, 10000) / 100,
            'purchase_date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertFalse($policy->viewAny($cashier), "Iteration {$iteration}: Cashier should not view any purchases");
        $this->assertFalse($policy->view($cashier, $purchase), "Iteration {$iteration}: Cashier should not view purchase");
        $this->assertFalse($policy->create($cashier), "Iteration {$iteration}: Cashier should not create purchases");
        $this->assertFalse($policy->update($cashier, $purchase), "Iteration {$iteration}: Cashier should not update purchase");
        $this->assertFalse($policy->delete($cashier, $purchase), "Iteration {$iteration}: Cashier should not delete purchase");
    }

    /**
     * Assert that cashier is denied all StockMovement operations
     */
    private function assertStockMovementDenial(User $cashier, int $iteration): void
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
        
        $this->assertFalse($policy->viewAny($cashier), "Iteration {$iteration}: Cashier should not view any stock movements");
        $this->assertFalse($policy->view($cashier, $stockMovement), "Iteration {$iteration}: Cashier should not view stock movement");
        $this->assertFalse($policy->create($cashier), "Iteration {$iteration}: Cashier should not create stock movements");
        $this->assertFalse($policy->update($cashier, $stockMovement), "Iteration {$iteration}: Cashier should not update stock movement");
        $this->assertFalse($policy->delete($cashier, $stockMovement), "Iteration {$iteration}: Cashier should not delete stock movement");
    }

    /**
     * Assert that cashier is denied all User operations
     */
    private function assertUserDenial(User $cashier, int $iteration): void
    {
        $policy = new UserPolicy();
        
        $targetUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $this->assertFalse($policy->viewAny($cashier), "Iteration {$iteration}: Cashier should not view any users");
        $this->assertFalse($policy->view($cashier, $targetUser), "Iteration {$iteration}: Cashier should not view user");
        $this->assertFalse($policy->create($cashier), "Iteration {$iteration}: Cashier should not create users");
        $this->assertFalse($policy->update($cashier, $targetUser), "Iteration {$iteration}: Cashier should not update user");
        $this->assertFalse($policy->delete($cashier, $targetUser), "Iteration {$iteration}: Cashier should not delete user");
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

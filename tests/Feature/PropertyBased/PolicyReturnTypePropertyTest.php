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
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-Based Tests for Policy Method Return Types
 * 
 * These tests verify that all policy methods return boolean values:
 * - Property 12: Policy Method Return Type
 * 
 * Each property is tested with 100 iterations using random data.
 */
class PolicyReturnTypePropertyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Role $ownerRole;
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
        
        // Create owner role
        $this->ownerRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'owner',
            'description' => 'Owner role',
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
     * Feature: role-based-policy-system, Property 12: Policy Method Return Type
     * 
     * For any policy class and any policy method (viewAny, view, create, update, delete),
     * when invoked with valid parameters, the method should return a boolean value.
     * 
     * Validates: Requirements 10.6
     * 
     * @test
     */
    public function property_12_all_policy_methods_return_boolean()
    {
        // Test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create a user with a random role
            $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
            $role = $this->randomRole();
            $user->roles()->attach($role);
            
            // Test all policy classes
            $this->assertProductPolicyReturnTypes($user, $i);
            $this->assertCategoryPolicyReturnTypes($user, $i);
            $this->assertSupplierPolicyReturnTypes($user, $i);
            $this->assertPurchasePolicyReturnTypes($user, $i);
            $this->assertSalePolicyReturnTypes($user, $i);
            $this->assertStockMovementPolicyReturnTypes($user, $i);
            $this->assertUserPolicyReturnTypes($user, $i);
        }
    }

    /**
     * Assert ProductPolicy methods return boolean
     */
    private function assertProductPolicyReturnTypes(User $user, int $iteration): void
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
        
        $this->assertIsBool($policy->viewAny($user), "Iteration {$iteration}: ProductPolicy::viewAny should return bool");
        $this->assertIsBool($policy->view($user, $product), "Iteration {$iteration}: ProductPolicy::view should return bool");
        $this->assertIsBool($policy->create($user), "Iteration {$iteration}: ProductPolicy::create should return bool");
        $this->assertIsBool($policy->update($user, $product), "Iteration {$iteration}: ProductPolicy::update should return bool");
        $this->assertIsBool($policy->delete($user, $product), "Iteration {$iteration}: ProductPolicy::delete should return bool");
    }

    /**
     * Assert CategoryPolicy methods return boolean
     */
    private function assertCategoryPolicyReturnTypes(User $user, int $iteration): void
    {
        $policy = new CategoryPolicy();
        
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Category {$iteration}",
        ]);
        
        $this->assertIsBool($policy->viewAny($user), "Iteration {$iteration}: CategoryPolicy::viewAny should return bool");
        $this->assertIsBool($policy->view($user, $category), "Iteration {$iteration}: CategoryPolicy::view should return bool");
        $this->assertIsBool($policy->create($user), "Iteration {$iteration}: CategoryPolicy::create should return bool");
        $this->assertIsBool($policy->update($user, $category), "Iteration {$iteration}: CategoryPolicy::update should return bool");
        $this->assertIsBool($policy->delete($user, $category), "Iteration {$iteration}: CategoryPolicy::delete should return bool");
    }

    /**
     * Assert SupplierPolicy methods return boolean
     */
    private function assertSupplierPolicyReturnTypes(User $user, int $iteration): void
    {
        $policy = new SupplierPolicy();
        
        $supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Supplier {$iteration}",
            'email' => "supplier{$iteration}@test.com",
        ]);
        
        $this->assertIsBool($policy->viewAny($user), "Iteration {$iteration}: SupplierPolicy::viewAny should return bool");
        $this->assertIsBool($policy->view($user, $supplier), "Iteration {$iteration}: SupplierPolicy::view should return bool");
        $this->assertIsBool($policy->create($user), "Iteration {$iteration}: SupplierPolicy::create should return bool");
        $this->assertIsBool($policy->update($user, $supplier), "Iteration {$iteration}: SupplierPolicy::update should return bool");
        $this->assertIsBool($policy->delete($user, $supplier), "Iteration {$iteration}: SupplierPolicy::delete should return bool");
    }

    /**
     * Assert PurchasePolicy methods return boolean
     */
    private function assertPurchasePolicyReturnTypes(User $user, int $iteration): void
    {
        $policy = new PurchasePolicy();
        
        $purchase = Purchase::create([
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $this->supplier->id,
            'total_amount' => rand(100, 10000) / 100,
            'purchase_date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertIsBool($policy->viewAny($user), "Iteration {$iteration}: PurchasePolicy::viewAny should return bool");
        $this->assertIsBool($policy->view($user, $purchase), "Iteration {$iteration}: PurchasePolicy::view should return bool");
        $this->assertIsBool($policy->create($user), "Iteration {$iteration}: PurchasePolicy::create should return bool");
        $this->assertIsBool($policy->update($user, $purchase), "Iteration {$iteration}: PurchasePolicy::update should return bool");
        $this->assertIsBool($policy->delete($user, $purchase), "Iteration {$iteration}: PurchasePolicy::delete should return bool");
    }

    /**
     * Assert SalePolicy methods return boolean
     */
    private function assertSalePolicyReturnTypes(User $user, int $iteration): void
    {
        $policy = new SalePolicy();
        
        $sale = Sale::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'total_amount' => rand(100, 10000) / 100,
            'payment_method' => $this->randomPaymentMethod(),
            'sale_date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertIsBool($policy->viewAny($user), "Iteration {$iteration}: SalePolicy::viewAny should return bool");
        $this->assertIsBool($policy->view($user, $sale), "Iteration {$iteration}: SalePolicy::view should return bool");
        $this->assertIsBool($policy->create($user), "Iteration {$iteration}: SalePolicy::create should return bool");
        $this->assertIsBool($policy->update($user, $sale), "Iteration {$iteration}: SalePolicy::update should return bool");
        $this->assertIsBool($policy->delete($user, $sale), "Iteration {$iteration}: SalePolicy::delete should return bool");
    }

    /**
     * Assert StockMovementPolicy methods return boolean
     */
    private function assertStockMovementPolicyReturnTypes(User $user, int $iteration): void
    {
        $policy = new StockMovementPolicy();
        
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => "Product Stock {$iteration}",
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
        
        $this->assertIsBool($policy->viewAny($user), "Iteration {$iteration}: StockMovementPolicy::viewAny should return bool");
        $this->assertIsBool($policy->view($user, $stockMovement), "Iteration {$iteration}: StockMovementPolicy::view should return bool");
        $this->assertIsBool($policy->create($user), "Iteration {$iteration}: StockMovementPolicy::create should return bool");
        $this->assertIsBool($policy->update($user, $stockMovement), "Iteration {$iteration}: StockMovementPolicy::update should return bool");
        $this->assertIsBool($policy->delete($user, $stockMovement), "Iteration {$iteration}: StockMovementPolicy::delete should return bool");
    }

    /**
     * Assert UserPolicy methods return boolean
     */
    private function assertUserPolicyReturnTypes(User $user, int $iteration): void
    {
        $policy = new UserPolicy();
        
        $targetUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $this->assertIsBool($policy->viewAny($user), "Iteration {$iteration}: UserPolicy::viewAny should return bool");
        $this->assertIsBool($policy->view($user, $targetUser), "Iteration {$iteration}: UserPolicy::view should return bool");
        $this->assertIsBool($policy->create($user), "Iteration {$iteration}: UserPolicy::create should return bool");
        $this->assertIsBool($policy->update($user, $targetUser), "Iteration {$iteration}: UserPolicy::update should return bool");
        $this->assertIsBool($policy->delete($user, $targetUser), "Iteration {$iteration}: UserPolicy::delete should return bool");
    }

    /**
     * Get a random role for the tenant
     */
    private function randomRole(): Role
    {
        $roleNames = ['owner', 'manager', 'admin', 'cashier'];
        $roleName = $roleNames[array_rand($roleNames)];
        
        return Role::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => $roleName],
            ['description' => ucfirst($roleName) . ' role']
        );
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

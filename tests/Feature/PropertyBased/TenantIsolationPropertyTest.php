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
 * Property-Based Tests for Tenant Isolation
 * 
 * These tests verify that tenant isolation is properly enforced:
 * - Property 10: Tenant Isolation Enforcement
 * - Property 11: Tenant Check Precedence
 * 
 * Each property is tested with 100 iterations using random data.
 */
class TenantIsolationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private Role $ownerRole1;
    private Role $ownerRole2;
    private Category $category1;
    private Category $category2;
    private Supplier $supplier1;
    private Supplier $supplier2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create two tenants
        $this->tenant1 = Tenant::create([
            'name' => 'Tenant 1',
            'email' => 'tenant1@example.com',
            'domain' => 'tenant1.example.com',
        ]);
        
        $this->tenant2 = Tenant::create([
            'name' => 'Tenant 2',
            'email' => 'tenant2@example.com',
            'domain' => 'tenant2.example.com',
        ]);
        
        // Create owner roles for both tenants
        $this->ownerRole1 = Role::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'owner',
            'description' => 'Owner role',
        ]);
        
        $this->ownerRole2 = Role::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'owner',
            'description' => 'Owner role',
        ]);
        
        // Create categories for both tenants
        $this->category1 = Category::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Category Tenant 1',
        ]);
        
        $this->category2 = Category::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Category Tenant 2',
        ]);
        
        // Create suppliers for both tenants
        $this->supplier1 = Supplier::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Supplier Tenant 1',
            'email' => 'supplier1@test.com',
        ]);
        
        $this->supplier2 = Supplier::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Supplier Tenant 2',
            'email' => 'supplier2@test.com',
        ]);
    }

    /**
     * Feature: role-based-policy-system, Property 10: Tenant Isolation Enforcement
     * 
     * For any authenticated user and any resource that does not belong to the user's tenant,
     * all resource-specific policy methods (view, update, delete) should return false
     * regardless of the user's role.
     * 
     * Validates: Requirements 8.1, 8.2
     * 
     * @test
     */
    public function property_10_tenant_isolation_is_enforced()
    {
        // Test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create a user in tenant 1 with a random role
            $user = User::factory()->create(['tenant_id' => $this->tenant1->id]);
            $role = $this->randomRole($this->tenant1);
            $user->roles()->attach($role);
            
            // Test cross-tenant access denial for all resource types
            $this->assertCrossTenantProductDenial($user, $i);
            $this->assertCrossTenantCategoryDenial($user, $i);
            $this->assertCrossTenantSupplierDenial($user, $i);
            $this->assertCrossTenantPurchaseDenial($user, $i);
            $this->assertCrossTenantSaleDenial($user, $i);
            $this->assertCrossTenantStockMovementDenial($user, $i);
            $this->assertCrossTenantUserDenial($user, $i);
        }
    }

    /**
     * Feature: role-based-policy-system, Property 11: Tenant Check Precedence
     * 
     * For any authenticated user with the owner role and any resource from a different tenant,
     * all resource-specific policy methods (view, update, delete) should return false,
     * demonstrating that tenant checks occur before role checks.
     * 
     * Validates: Requirements 8.3
     * 
     * @test
     */
    public function property_11_tenant_check_has_precedence_over_role_check()
    {
        // Test with 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Create an owner user in tenant 1
            $owner = User::factory()->create(['tenant_id' => $this->tenant1->id]);
            $owner->roles()->attach($this->ownerRole1);
            
            // Despite being an owner, they should not access tenant 2 resources
            $this->assertCrossTenantProductDenial($owner, $i);
            $this->assertCrossTenantCategoryDenial($owner, $i);
            $this->assertCrossTenantSupplierDenial($owner, $i);
            $this->assertCrossTenantPurchaseDenial($owner, $i);
            $this->assertCrossTenantSaleDenial($owner, $i);
            $this->assertCrossTenantStockMovementDenial($owner, $i);
            $this->assertCrossTenantUserDenial($owner, $i);
        }
    }

    /**
     * Assert cross-tenant Product access is denied
     */
    private function assertCrossTenantProductDenial(User $user, int $iteration): void
    {
        $policy = new ProductPolicy();
        
        $product = Product::create([
            'tenant_id' => $this->tenant2->id,
            'name' => "Product T2 {$iteration}",
            'sku' => "SKU-T2-{$iteration}",
            'category_id' => $this->category2->id,
            'supplier_id' => $this->supplier2->id,
            'stock' => rand(0, 100),
            'cost_price' => rand(50, 500) / 10,
            'price' => rand(100, 1000) / 10,
        ]);
        
        $this->assertFalse($policy->view($user, $product), "Iteration {$iteration}: Cross-tenant product view should be denied");
        $this->assertFalse($policy->update($user, $product), "Iteration {$iteration}: Cross-tenant product update should be denied");
        $this->assertFalse($policy->delete($user, $product), "Iteration {$iteration}: Cross-tenant product delete should be denied");
    }

    /**
     * Assert cross-tenant Category access is denied
     */
    private function assertCrossTenantCategoryDenial(User $user, int $iteration): void
    {
        $policy = new CategoryPolicy();
        
        $category = Category::create([
            'tenant_id' => $this->tenant2->id,
            'name' => "Category T2 {$iteration}",
        ]);
        
        $this->assertFalse($policy->view($user, $category), "Iteration {$iteration}: Cross-tenant category view should be denied");
        $this->assertFalse($policy->update($user, $category), "Iteration {$iteration}: Cross-tenant category update should be denied");
        $this->assertFalse($policy->delete($user, $category), "Iteration {$iteration}: Cross-tenant category delete should be denied");
    }

    /**
     * Assert cross-tenant Supplier access is denied
     */
    private function assertCrossTenantSupplierDenial(User $user, int $iteration): void
    {
        $policy = new SupplierPolicy();
        
        $supplier = Supplier::create([
            'tenant_id' => $this->tenant2->id,
            'name' => "Supplier T2 {$iteration}",
            'email' => "supplier-t2-{$iteration}@test.com",
        ]);
        
        $this->assertFalse($policy->view($user, $supplier), "Iteration {$iteration}: Cross-tenant supplier view should be denied");
        $this->assertFalse($policy->update($user, $supplier), "Iteration {$iteration}: Cross-tenant supplier update should be denied");
        $this->assertFalse($policy->delete($user, $supplier), "Iteration {$iteration}: Cross-tenant supplier delete should be denied");
    }

    /**
     * Assert cross-tenant Purchase access is denied
     */
    private function assertCrossTenantPurchaseDenial(User $user, int $iteration): void
    {
        $policy = new PurchasePolicy();
        
        $purchase = Purchase::create([
            'tenant_id' => $this->tenant2->id,
            'supplier_id' => $this->supplier2->id,
            'total_amount' => rand(100, 10000) / 100,
            'purchase_date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertFalse($policy->view($user, $purchase), "Iteration {$iteration}: Cross-tenant purchase view should be denied");
        $this->assertFalse($policy->update($user, $purchase), "Iteration {$iteration}: Cross-tenant purchase update should be denied");
        $this->assertFalse($policy->delete($user, $purchase), "Iteration {$iteration}: Cross-tenant purchase delete should be denied");
    }

    /**
     * Assert cross-tenant Sale access is denied
     */
    private function assertCrossTenantSaleDenial(User $user, int $iteration): void
    {
        $policy = new SalePolicy();
        
        $tenant2User = User::factory()->create(['tenant_id' => $this->tenant2->id]);
        
        $sale = Sale::create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $tenant2User->id,
            'total_amount' => rand(100, 10000) / 100,
            'payment_method' => $this->randomPaymentMethod(),
            'sale_date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertFalse($policy->view($user, $sale), "Iteration {$iteration}: Cross-tenant sale view should be denied");
        $this->assertFalse($policy->update($user, $sale), "Iteration {$iteration}: Cross-tenant sale update should be denied");
        $this->assertFalse($policy->delete($user, $sale), "Iteration {$iteration}: Cross-tenant sale delete should be denied");
    }

    /**
     * Assert cross-tenant StockMovement access is denied
     */
    private function assertCrossTenantStockMovementDenial(User $user, int $iteration): void
    {
        $policy = new StockMovementPolicy();
        
        $product = Product::create([
            'tenant_id' => $this->tenant2->id,
            'name' => "Product Stock T2 {$iteration}",
            'sku' => "SKU-STOCK-T2-{$iteration}",
            'category_id' => $this->category2->id,
            'supplier_id' => $this->supplier2->id,
            'stock' => rand(0, 100),
            'cost_price' => rand(50, 500) / 10,
            'price' => rand(100, 1000) / 10,
        ]);
        
        $stockMovement = StockMovement::create([
            'tenant_id' => $this->tenant2->id,
            'product_id' => $product->id,
            'quantity' => rand(1, 50),
            'type' => $this->randomMovementType(),
            'date' => now()->subDays(rand(0, 30)),
        ]);
        
        $this->assertFalse($policy->view($user, $stockMovement), "Iteration {$iteration}: Cross-tenant stock movement view should be denied");
        $this->assertFalse($policy->update($user, $stockMovement), "Iteration {$iteration}: Cross-tenant stock movement update should be denied");
        $this->assertFalse($policy->delete($user, $stockMovement), "Iteration {$iteration}: Cross-tenant stock movement delete should be denied");
    }

    /**
     * Assert cross-tenant User access is denied
     */
    private function assertCrossTenantUserDenial(User $user, int $iteration): void
    {
        $policy = new UserPolicy();
        
        $targetUser = User::factory()->create(['tenant_id' => $this->tenant2->id]);
        
        $this->assertFalse($policy->view($user, $targetUser), "Iteration {$iteration}: Cross-tenant user view should be denied");
        $this->assertFalse($policy->update($user, $targetUser), "Iteration {$iteration}: Cross-tenant user update should be denied");
        $this->assertFalse($policy->delete($user, $targetUser), "Iteration {$iteration}: Cross-tenant user delete should be denied");
    }

    /**
     * Get a random role for a tenant
     */
    private function randomRole(Tenant $tenant): Role
    {
        $roleNames = ['owner', 'manager', 'admin', 'cashier'];
        $roleName = $roleNames[array_rand($roleNames)];
        
        return Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $roleName],
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

<?php

namespace Tests\Unit\Policies;

use App\Models\Product;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProductPolicy $policy;
    private Tenant $tenant;
    private Role $ownerRole;
    private Role $managerRole;
    private Role $adminRole;
    private Role $cashierRole;
    private $category;
    private $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new ProductPolicy();
        
        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'domain' => 'test.example.com',
        ]);
        
        // Create category and supplier for products
        $this->category = \App\Models\Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Category',
        ]);
        
        $this->supplier = \App\Models\Supplier::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
        ]);
        
        // Create roles
        $this->ownerRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'owner',
            'description' => 'Owner role',
        ]);
        
        $this->managerRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'manager',
            'description' => 'Manager role',
        ]);
        
        $this->adminRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'admin',
            'description' => 'Admin role',
        ]);
        
        $this->cashierRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'cashier',
            'description' => 'Cashier role',
        ]);
    }

    /** @test */
    public function owner_can_view_any_products()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->ownerRole);
        
        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function manager_can_view_any_products()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->managerRole);
        
        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function admin_cannot_view_any_products()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->adminRole);
        
        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function cashier_cannot_view_any_products()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->cashierRole);
        
        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function owner_can_view_product_in_same_tenant()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->ownerRole);
        
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'stock' => 10,
            'cost_price' => 80.00,
            'price' => 100.00,
        ]);
        
        $this->assertTrue($this->policy->view($user, $product));
    }

    /** @test */
    public function owner_cannot_view_product_in_different_tenant()
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'email' => 'other@example.com',
            'domain' => 'other.example.com',
        ]);
        
        $otherCategory = \App\Models\Category::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Category',
        ]);
        
        $otherSupplier = \App\Models\Supplier::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Supplier',
            'email' => 'other-supplier@test.com',
        ]);
        
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->ownerRole);
        
        $product = Product::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Product',
            'sku' => 'OTHER-001',
            'category_id' => $otherCategory->id,
            'supplier_id' => $otherSupplier->id,
            'stock' => 10,
            'cost_price' => 80.00,
            'price' => 100.00,
        ]);
        
        $this->assertFalse($this->policy->view($user, $product));
    }

    /** @test */
    public function manager_can_create_products()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->managerRole);
        
        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function admin_cannot_create_products()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->adminRole);
        
        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function manager_can_update_product_in_same_tenant()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->managerRole);
        
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'sku' => 'TEST-002',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'stock' => 10,
            'cost_price' => 80.00,
            'price' => 100.00,
        ]);
        
        $this->assertTrue($this->policy->update($user, $product));
    }

    /** @test */
    public function manager_cannot_update_product_in_different_tenant()
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'email' => 'other2@example.com',
            'domain' => 'other.example.com',
        ]);
        
        $otherCategory = \App\Models\Category::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Category',
        ]);
        
        $otherSupplier = \App\Models\Supplier::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Supplier',
            'email' => 'other-supplier2@test.com',
        ]);
        
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->managerRole);
        
        $product = Product::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Product',
            'sku' => 'OTHER-002',
            'category_id' => $otherCategory->id,
            'supplier_id' => $otherSupplier->id,
            'stock' => 10,
            'cost_price' => 80.00,
            'price' => 100.00,
        ]);
        
        $this->assertFalse($this->policy->update($user, $product));
    }

    /** @test */
    public function owner_can_delete_product_in_same_tenant()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->ownerRole);
        
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'sku' => 'TEST-003',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'stock' => 10,
            'cost_price' => 80.00,
            'price' => 100.00,
        ]);
        
        $this->assertTrue($this->policy->delete($user, $product));
    }

    /** @test */
    public function cashier_cannot_delete_products()
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->roles()->attach($this->cashierRole);
        
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'sku' => 'TEST-004',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'stock' => 10,
            'cost_price' => 80.00,
            'price' => 100.00,
        ]);
        
        $this->assertFalse($this->policy->delete($user, $product));
    }
}

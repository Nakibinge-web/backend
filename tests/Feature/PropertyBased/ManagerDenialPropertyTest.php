<?php

namespace Tests\Feature\PropertyBased;

use App\Models\Role;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\SalePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Property-Based Tests for Manager Denial Rules
 * 
 * These tests verify that managers are properly denied access to:
 * - User management operations (Property 3)
 * - Sale operations (Property 4)
 * 
 * Each property is tested with 100 iterations using random data.
 */
class ManagerDenialPropertyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            'domain' => 'test.example.com',
        ]);
        
        // Create manager role
        $this->managerRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'manager',
            'description' => 'Manager role',
        ]);
    }

    /**
     * Feature: role-based-policy-system, Property 3: Manager User Management Denial
     * 
     * For any authenticated user with the manager role and any User resource,
     * all policy methods (viewAny, view, create, update, delete) should return false.
     * 
     * Validates: Requirements 3.1, 3.2, 3.3, 3.4
     * 
     * @test
     */
    public function property_3_manager_cannot_manage_users()
    {
        $policy = new UserPolicy();
        
        // Run 100 iterations with random users
        for ($i = 0; $i < 100; $i++) {
            // Create a manager user
            $manager = User::factory()->create(['tenant_id' => $this->tenant->id]);
            $manager->roles()->attach($this->managerRole);
            
            // Create a target user in the same tenant
            $targetUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
            
            // Assert all operations are denied
            $this->assertFalse(
                $policy->viewAny($manager),
                "Iteration {$i}: Manager should not be able to view any users"
            );
            
            $this->assertFalse(
                $policy->view($manager, $targetUser),
                "Iteration {$i}: Manager should not be able to view user {$targetUser->id}"
            );
            
            $this->assertFalse(
                $policy->create($manager),
                "Iteration {$i}: Manager should not be able to create users"
            );
            
            $this->assertFalse(
                $policy->update($manager, $targetUser),
                "Iteration {$i}: Manager should not be able to update user {$targetUser->id}"
            );
            
            $this->assertFalse(
                $policy->delete($manager, $targetUser),
                "Iteration {$i}: Manager should not be able to delete user {$targetUser->id}"
            );
        }
    }

    /**
     * Feature: role-based-policy-system, Property 4: Manager Sales Denial
     * 
     * For any authenticated user with the manager role and any Sale resource,
     * all policy methods (viewAny, view, create, update, delete) should return false.
     * 
     * Validates: Requirements 3.5, 3.6, 3.7, 3.8
     * 
     * @test
     */
    public function property_4_manager_cannot_manage_sales()
    {
        $policy = new SalePolicy();
        
        // Run 100 iterations with random sales
        for ($i = 0; $i < 100; $i++) {
            // Create a manager user
            $manager = User::factory()->create(['tenant_id' => $this->tenant->id]);
            $manager->roles()->attach($this->managerRole);
            
            // Create a sale in the same tenant
            $sale = Sale::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $manager->id,
                'total_amount' => rand(100, 10000) / 100, // Random amount between 1.00 and 100.00
                'payment_method' => $this->randomPaymentMethod(),
                'sale_date' => now()->subDays(rand(0, 30)),
            ]);
            
            // Assert all operations are denied
            $this->assertFalse(
                $policy->viewAny($manager),
                "Iteration {$i}: Manager should not be able to view any sales"
            );
            
            $this->assertFalse(
                $policy->view($manager, $sale),
                "Iteration {$i}: Manager should not be able to view sale {$sale->id}"
            );
            
            $this->assertFalse(
                $policy->create($manager),
                "Iteration {$i}: Manager should not be able to create sales"
            );
            
            $this->assertFalse(
                $policy->update($manager, $sale),
                "Iteration {$i}: Manager should not be able to update sale {$sale->id}"
            );
            
            $this->assertFalse(
                $policy->delete($manager, $sale),
                "Iteration {$i}: Manager should not be able to delete sale {$sale->id}"
            );
        }
    }

    /**
     * Generate a random payment method for sales
     */
    private function randomPaymentMethod(): string
    {
        $methods = ['cash', 'card', 'transfer', 'mobile'];
        return $methods[array_rand($methods)];
    }
}

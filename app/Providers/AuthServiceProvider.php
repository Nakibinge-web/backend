<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\SalePolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
        Supplier::class => SupplierPolicy::class,
        Purchase::class => PurchasePolicy::class,
        Sale::class => SalePolicy::class,
        StockMovement::class => StockMovementPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

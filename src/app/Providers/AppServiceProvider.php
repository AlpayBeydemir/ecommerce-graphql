<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Product Observer for Elasticsearch auto-indexing
        Product::observe(ProductObserver::class);

        // Configure Passport token lifetimes
        // Note: This app uses Personal Access Tokens (via createToken()), not OAuth2 flow

        // OAuth2 tokens (not currently used, but configured for future)
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        // Personal Access Tokens (currently in use)
        // This is the expiration time returned to clients in register/login responses
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}

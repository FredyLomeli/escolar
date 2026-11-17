<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        if (config('app.trust_proxies')) {
            TrustProxies::at('*');

            if (! app()->runningUnitTests() && str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        Model::preventLazyLoading(! app()->isProduction());

        Gate::policy(User::class, UserPolicy::class);

        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-catalogs', fn (User $user): bool => $user->isAdmin() || $user->isCoordinator());
        Gate::define('manage-planning', fn (User $user): bool => $user->isAdmin() || $user->isCoordinator());
    }
}

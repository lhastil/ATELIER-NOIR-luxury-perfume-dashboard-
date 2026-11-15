<?php

namespace App\Providers;

use App\Models\User;
use App\View\Composers\NotificationsComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        // The top bar's notification bell, on every admin page.
        View::composer('components.admin.topbar', NotificationsComposer::class);

        // One gate per section, e.g. "access-products", from User::SECTION_ROLES.
        // Routes use them as middleware (can:access-products) and views with @can.
        foreach (array_keys(User::SECTION_ROLES) as $section) {
            Gate::define("access-{$section}", fn (User $user) => $user->canAccessSection($section));
        }
    }
}

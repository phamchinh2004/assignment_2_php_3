<?php

namespace App\Providers;

use App\Models\FeatureAnnouncement;
use App\Services\FeatureAnnouncementService;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

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
        Carbon::setLocale('vi');

        View::composer('admin.layouts.master', function ($view) {
            $user = auth()->user();

            if (!$user || !in_array($user->role, FeatureAnnouncement::TARGET_ROLES, true)) {
                $view->with('featureAnnouncements', collect());
                return;
            }

            $view->with(
                'featureAnnouncements',
                app(FeatureAnnouncementService::class)->getUnreadAnnouncements($user)
            );
        });
    }
}

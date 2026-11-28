<?php

namespace App\Providers;

use App\Models\Customer;
use App\Policies\CustomerPolicy;
use App\Services\ColorPaletteService;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailtrap\Transport\MailtrapTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Customer::class, CustomerPolicy::class);

        if (app()->environment('production') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            URL::forceScheme('https');
        }
        Mail::extend('mailtrap', function ($config) {
            $factory = new MailtrapTransportFactory;

            $inboxId = config('services.mailtrap.inbox_id');
            $scheme = $inboxId ? 'mailtrap+sandbox' : 'mailtrap+api';
            $options = $inboxId ? ['inboxId' => $inboxId] : [];

            return $factory->create(new Dsn(
                $scheme,
                'default',
                config('services.mailtrap.secret'),
                null,
                null,
                $options
            ));
        });

        View::composer('*', function ($view) {
            static $cachedTenantId = -1;
            static $cachedThemeData = null;

            $currentTenant = TenantManager::getTenant();
            $tenantId = $currentTenant?->id;

            if ($cachedThemeData === null || $cachedTenantId !== $tenantId) {
                try {
                    $tenantTheme = $currentTenant?->settings['theme'] ?? null;
                    $primaryColor = $tenantTheme['primary_color'] ?? '#4f46e5';
                    $accentColor = $tenantTheme['accent_color'] ?? '#6366f1';
                    $brandShades = $tenantTheme['shades'] ?? ColorPaletteService::generateShades($primaryColor);

                    $cachedThemeData = [
                        'primaryColor' => $primaryColor,
                        'accentColor' => $accentColor,
                        'brandShades' => $brandShades,
                    ];
                    $cachedTenantId = $tenantId;
                } catch (\Throwable $e) {
                    $cachedThemeData = [
                        'primaryColor' => '#4f46e5',
                        'accentColor' => '#6366f1',
                        'brandShades' => ColorPaletteService::generateShades('#4f46e5'),
                    ];
                    $cachedTenantId = null;
                }
            }

            // Always resolve currentUser dynamically for the current request
            $currentUser = Auth::user();

            $view->with(array_merge($cachedThemeData, [
                'currentUser' => $currentUser,
                'currentTenant' => $currentTenant,
            ]));
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Mailtrap\Transport\MailtrapTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('mailtrap', function ($config) {
            $factory = new MailtrapTransportFactory();
            
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
            try {
                if (Schema::hasTable('users')) {
                    $currentUser = Auth::user() ?? User::first();
                    $view->with('currentUser', $currentUser);
                }
                if (Schema::hasTable('tenants')) {
                    $currentTenant = \App\Services\TenantManager::getTenant();
                    $view->with('currentTenant', $currentTenant);

                    $tenantTheme = $currentTenant?->settings['theme'] ?? null;
                    $primaryColor = $tenantTheme['primary_color'] ?? '#4f46e5';
                    $accentColor = $tenantTheme['accent_color'] ?? '#6366f1';
                    $brandShades = $tenantTheme['shades'] ?? \App\Services\ColorPaletteService::generateShades($primaryColor);

                    $view->with('primaryColor', $primaryColor);
                    $view->with('accentColor', $accentColor);
                    $view->with('brandShades', $brandShades);
                }
            } catch (\Throwable $e) {
                $view->with('currentUser', null);
                $view->with('currentTenant', null);
                $view->with('primaryColor', '#4f46e5');
                $view->with('accentColor', '#6366f1');
                $view->with('brandShades', \App\Services\ColorPaletteService::generateShades('#4f46e5'));
            }
        });
    }
}

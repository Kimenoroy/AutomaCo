<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Azure\AzureExtendSocialite;

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
        // 1. Configuración de Reset Password (que ya tenías)
        ResetPassword::createUrlUsing(function ($user, $token) {
            // Apuntamos al puerto 3000 (donde correrá React) o al dominio del frontend
            $frontenUrl = env('FRONTEND_URL');
            return $frontenUrl . "/reset-password?token=" . $token . "&email=" . $user->email;
        });

        // 2. AGREGAR ESTO: Configuración para que funcione Outlook (Azure)
        Event::listen(
            SocialiteWasCalled::class,
            AzureExtendSocialite::class . '@handle'
        );
    }
}

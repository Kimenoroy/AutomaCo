<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\N8nController;
use App\Http\Controllers\DashboardController;

/*Rutas Públicas (Cualquiera puede entrar)*/

Route::post("/register", [AuthController::class, "register"])->name("register");
Route::post('/login', [AuthController::class, 'login'])->name("login");
Route::post('/send-reset-link', [PasswordResetController::class, 'sendResetLink'])->name("send-reset-link");
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name("reset-password");


//PARA LA AUTH
Route::get('/auth/{driver}/callback', [SocialAuthController::class, 'handleCallback']);

/*Rutas Protegidas (Necesitan estar autenticado)*/
Route::middleware('auth:sanctum')->group(function () {

    // Ruta para activar cuenta (no requiere cuenta activada)
    Route::post('/activate', [AuthController::class, 'activate'])->name("activate");

    // Rutas que requieren cuenta activada
    Route::middleware('account.active')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        //Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Rutas de proveedores de email
        Route::get('/providers', [AuthController::class, 'getProviders'])->name('providers.list');
        Route::post('/select-provider', [AuthController::class, 'selectProvider'])->name('providers.select');

        //AUTH
        Route::get('/auth/social/redirect', [SocialAuthController::class, 'getRedirectUrl']);

        //USUARIOS
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // FACTURAS (INVOICES)
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
        Route::get('/invoices/{id}/download/pdf', [InvoiceController::class, 'downloadPdf']);
        Route::get('/invoices/{id}/download/json', [InvoiceController::class, 'downloadJson']);

        // AJUSTES (SETTINGS)
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile']);
        Route::put('/settings/password', [SettingsController::class, 'updatePassword']);
        Route::delete('/settings/provider/{id}', [SettingsController::class, 'unlinkProvider']);
        Route::put('/settings/account', [SettingsController::class, 'destroy']);
    });
});

// Ruta pública para recibir facturas desde n8n (sin autenticación)
Route::post('/invoices/webhook', [InvoiceController::class, 'store']);

Route::middleware(['auth'])->group(function () {
    // Ruta para que el usuario presione el botón de sincronizar
    Route::post('/n8n/sync-invoices', [N8nController::class, 'syncInvoices'])->name('n8n.sync');
});



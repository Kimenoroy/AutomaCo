<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Models\EmailProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\N8nService;

use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    private function getFrontendUrl()
    {
        return config('services.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
    }

    /**
     * Paso 1: El Frontend pide la URL (Recibe ID 1 o 2)
     */
    public function getRedirectUrl(Request $request)
    {
        $request->validate(['provider_id' => 'required|exists:email_providers,id']);

        // Obtenemos el usuario que está pidiendo
        $user = $request->user();

        $origin = $request->query('origin', 'dashboard');

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }


        // Generamos un ID único temporal 
        $state = Str::random(40);

        // Guardamos en caché
        cache()->put('social_auth_state_' . $state, [
            'user_id' => $user->id,
            'origin' => $origin
        ], 300);

        $provider = EmailProvider::find($request->provider_id);

        // Mapear tu ID de base de datos al driver de Socialite
        $driver = match ($provider->name) {
            'google' => 'google',
            'outlook' => 'azure', // Azure se usa para Outlook/Microsoft
            default => null,
        };

        if (!$driver) {
            return response()->json(['error' => 'Proveedor no soportado'], 400);
        }

        // Definir Scopes: Permisos para leer correos (VITAL PARA N8N)
        $scopes = match ($driver) {
            'google' => ['https://www.googleapis.com/auth/gmail.readonly', 'email', 'profile'],
            'azure' => ['Mail.Read', 'User.Read', 'offline_access'], // offline_access es OBLIGATORIO para refresh_token en Microsoft
            default => [],
        };

        // Generar URL
        // 'access_type' => 'offline' y 'prompt' => 'consent' son OBLIGATORIOS en Google para obtener Refresh Token
        $socialiteDriver = Socialite::driver($driver);

        /** @var \Laravel\Socialite\Two\AbstractProvider $socialiteDriver */
        $url = $socialiteDriver
            ->scopes($scopes)
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'state' => $state // <--- 4. ENVIAMOS EL STATE A GOOGLE
            ])
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * Paso 2: Callback (Cuando vuelven de Google/Outlook)
     * Este endpoint lo llama Google
     */
    // Inyectamos N8nService en el método
    public function handleCallback(Request $request, $driver, N8nService $n8nService)
    {
        // 1. Preparamos valores por defecto para evitar errores en el catch
        $origin = 'dashboard';
        $targetPath = '/dashboard';

        try {
            $state = $request->input('state');
            
            // Verificación de seguridad del estado
            if (!$state) {
                return redirect($this->getFrontendUrl() . "/dashboard?status=error&message=Estado inválido");
            }

            $cachedData = cache()->pull('social_auth_state_' . $state);

            if (!$cachedData) {
                return redirect($this->getFrontendUrl() . "/dashboard?status=error&message=La sesión expiró, intenta de nuevo");
            }

            // Normalizar datos de caché
            if (is_array($cachedData)) {
                $userId = $cachedData['user_id'];
                $origin = $cachedData['origin'];
            } else {
                $userId = $cachedData;
            }

            // Configurar ruta de éxito/error basada en el origen
            // Usamos #email para que el frontend abra la pestaña correcta
            $targetPath = ($origin === 'settings') ? '/settings#email' : '/dashboard';

            // Obtener datos del proveedor (Google/Outlook)
            $socialiteDriver = Socialite::driver($driver);
            $socialUser = $socialiteDriver->stateless()->user();
            
            $email = $socialUser->getEmail();
            $socialId = $socialUser->getId();

            // =========================================================================
            // CORRECCIÓN CRÍTICA: VALIDACIÓN GLOBAL
            // =========================================================================
            // Buscamos si este correo O este ID de Google ya existen en la BD (sin importar el usuario)
            $existingAccount = ConnectedAccount::where(function($query) use ($email, $socialId) {
                $query->where('email', $email)
                      ->orWhere('provider_user_id', $socialId);
            })->first();

            // Si existe la cuenta Y pertenece a OTRO usuario (ID diferente al mío)
            if ($existingAccount && $existingAccount->user_id != $userId) {
                Log::warning("Intento de vinculación duplicada: User {$userId} intentó vincular {$email} que pertenece a User {$existingAccount->user_id}");
                
                // DETENEMOS TODO AQUI y devolvemos error.
                // IMPORTANTE: Esto evita que llegue al 'linked_success' de abajo.
                return redirect($this->getFrontendUrl() . $targetPath . "?status=error&message=Esta cuenta de correo ya está vinculada a otro usuario.");
            }
            // =========================================================================

            $providerName = $driver === 'azure' ? 'outlook' : 'google';
            $provider = EmailProvider::where('name', $providerName)->first();

            if (!$provider) {
                return redirect($this->getFrontendUrl() . "/dashboard?status=error&message=Proveedor no configurado en el sistema");
            }

            // Si pasamos la validación, ahora sí Guardamos/Actualizamos
            ConnectedAccount::updateOrCreate(
                [
                    'user_id' => $userId,
                    'provider_user_id' => $socialId, // Buscamos por mi ID y el ID de Google
                ],
                [
                    'email_provider_id' => $provider->id,
                    'name' => $socialUser->getName(),
                    'email' => $email,
                    'avatar' => $socialUser->getAvatar(),
                    'token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken,
                    'expires_at' => property_exists($socialUser, 'expiresIn') ? now()->addSeconds($socialUser->expiresIn) : null,
                ]
            );

            // Enviar a N8N
            try {
                $n8nService->sendProviderIdentifier([
                    'user_id' => $userId,
                    'provider' => $providerName,
                    'email' => $email,
                    'access_token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken,
                    'expires_in' => property_exists($socialUser, 'expiresIn') ? $socialUser->expiresIn : 3600,
                ]);
            } catch (\Exception $e) {
                Log::error("Error enviando a N8N: " . $e->getMessage());
                // No detenemos el flujo si falla N8N, pero lo logueamos
            }

            // ÉXITO
            return redirect($this->getFrontendUrl() . $targetPath . "?status=linked_success");

        } catch (\Exception $e) {
            Log::error("Error Fatal en SocialAuth: " . $e->getMessage());
            
            // REDIRECCIÓN SEGURA EN CASO DE ERROR (CATCH)
            // Aseguramos que la URL esté bien formada incluso si falla el try
            return redirect($this->getFrontendUrl() . $targetPath . "?status=error&message=Error interno del servidor");
        }
    }

    public function handleCallbackSettings(Request $request, $driver, N8nService $n8nService)
    {
        try {
            $state = $request->input('state');

            // Recuperamos el usuario que inició el proceso
            $userId = cache()->pull('social_auth_state_' . $state);

            if (!$userId) {
                // CAMBIO AQUI: Redirigir a settings con error
                return redirect($this->getFrontendUrl() . "/settings?status=error&message=La sesión expiró o es inválida");
            }

            /** @var \Laravel\Socialite\Two\AbstractProvider $socialiteDriver */
            $socialiteDriver = Socialite::driver($driver);
            $socialUser = $socialiteDriver->stateless()->user();

            $providerName = $driver === 'azure' ? 'outlook' : 'google';
            $provider = EmailProvider::where('name', $providerName)->first();

            if (!$provider) {
                Log::error("Proveedor de email no encontrado: {$providerName}");
                return redirect($this->getFrontendUrl() . "/settings?status=error&message=Proveedor no configurado");
            }

            ConnectedAccount::updateOrCreate(
                [
                    'user_id' => $userId,
                    'provider_user_id' => $socialUser->getId(),
                ],
                [
                    'email_provider_id' => $provider->id,
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken,
                    'expires_at' => property_exists($socialUser, 'expiresIn') ? now()->addSeconds($socialUser->expiresIn) : null,
                ]
            );

            // Enviar credenciales a N8N
            $n8nService->sendProviderIdentifier([
                'user_id' => $userId,
                'provider' => $providerName,
                'email' => $socialUser->getEmail(),
                'access_token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
                'expires_in' => property_exists($socialUser, 'expiresIn') ? $socialUser->expiresIn : 3600,
            ]);

            // CAMBIO PRINCIPAL: Redirigir a /settings con éxito
            return redirect($this->getFrontendUrl() . "/settings?status=linked_success");
        } catch (\Exception $e) {
            Log::error("Error en SocialAuth handleCallback: " . $e->getMessage());
            // CAMBIO AQUI: Redirigir a settings con error
            return redirect($this->getFrontendUrl() . "/settings?status=error&message=Error interno al vincular cuenta");
        }
    }
}

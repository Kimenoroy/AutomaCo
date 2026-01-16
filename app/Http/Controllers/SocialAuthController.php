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

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }


        // Generamos un ID único temporal 
        $state = Str::random(40);

        // Guardamos en caché
        cache()->put('social_auth_state_' . $state, $user->id, 300);

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
        try {

            $state = $request->input('state');
            $userId = cache()->pull('social_auth_state_' . $state);

            if (!$userId) {
                return redirect($this->getFrontendUrl() . "/dashboard?status=error&message=La sesión expiró o es inválida");
            }

            /** @var \Laravel\Socialite\Two\AbstractProvider $socialiteDriver */
            $socialiteDriver = Socialite::driver($driver);
            $socialUser = $socialiteDriver->stateless()->user();

            // ... (Tu código para buscar provider y guardar en DB) ...
            $providerName = $driver === 'azure' ? 'outlook' : 'google';
            $provider = EmailProvider::where('name', $providerName)->first();

            if (!$provider) {
                Log::error("Proveedor de email no encontrado: {$providerName}");
                return redirect($this->getFrontendUrl() . "/dashboard?status=error&message=Proveedor no configurado");
            }

            ConnectedAccount::updateOrCreate(
                // ... (Tus datos existentes) ...
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
                    'refresh_token' => $socialUser->refreshToken, // <--- IMPORTANTE: Asegúrate de tener esto
                    'expires_at' => property_exists($socialUser, 'expiresIn') ? now()->addSeconds($socialUser->expiresIn) : null,
                ]
            );

            // --- NUEVO CÓDIGO PARA ENVIAR A N8N ---

            $n8nService->sendProviderIdentifier([ // <--- CORREGIDO: Método correcto del servicio
                'user_id' => $userId,
                'provider' => $providerName,
                'email' => $socialUser->getEmail(),
                'access_token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
                'expires_in' => property_exists($socialUser, 'expiresIn') ? $socialUser->expiresIn : 3600,
            ]);

            // ---------------------------------------

            return redirect($this->getFrontendUrl() . "/dashboard?status=linked_success");
        } catch (\Exception $e) {
            Log::error("Error en SocialAuth handleCallback: " . $e->getMessage());
            return redirect($this->getFrontendUrl() . "/dashboard?status=error&message=Error interno al vincular cuenta");
        }
    }
}

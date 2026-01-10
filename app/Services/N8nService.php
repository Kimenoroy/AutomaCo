<?php

    namespace App\Services;

    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    
    class N8nService
    {
        protected string $webhookUrl;

        public function __construct()
        {
            $this->webhookUrl = config('services.n8n.webhook_url', env('N8N_WEBHOOK_URL'));
        }

        /**
         * Enviar credenciales del proveedor de email a n8n
         *
         * @param array $data
         * @return bool
         */
        public function sendProviderIdentifier(array $data): bool
        {

            $credentialsWebhookUrl = config('services.n8n.credentials_webhook_url', env('N8N_CREDENTIALS_WEBHOOK_URL'));

            if (empty($credentialsWebhookUrl)) {
                Log::warning('N8N_CREDENTIALS_WEBHOOK_URL no está configurado');
                return false;
            }

            try {
                $response = Http::post($credentialsWebhookUrl, [
                    'user_id' => $data['user_id'],
                    'provider' => $data['provider'],
                    'email' => $data['email'],
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_in' => $data['expires_in'],
                    'timestamp' => now()->toIso8601String(),
                ]);

                if ($response->successful()) {
                    Log::info('Identificador de proveedor enviado a n8n exitosamente', [
                        'user_id' => $data['user_id'],
                        'provider_identifier' => $data['provider'],
                    ]);
                    return true;
                }

                Log::error('Error al enviar a n8n', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            } catch (\Exception $e) {
                Log::error('Excepción al enviar a n8n', [
                    'message' => $e->getMessage(),
                    'user_id' => $data['user_id'] ?? 'unknown',
                ]);
                return false;
            }
        }
    }


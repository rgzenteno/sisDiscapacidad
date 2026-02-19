<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiKey;
    private string $baseUrl;
    private array $defaultHeaders;

    public function __construct()
    {
        $this->apiKey = config('services.whatsapp.api_key');
        $this->baseUrl = config('services.whatsapp.url');
        $this->defaultHeaders = [
            "Authorization: Bearer {$this->apiKey}",
            "Content-Type: application/json"
        ];

        
        Log::info('Headers enviados:', $this->defaultHeaders);
        Log::info('Iniciando configuración de WhatsApp API', [
            'apiKey' => $this->apiKey,
            'baseUrl' => $this->baseUrl,
            'defaultHeaders' => array_keys($this->defaultHeaders),
        ]);


        // Validar configuración
        $this->validateConfig();
    }

    private function validateConfig(): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('WhatsApp API Key no está configurada');
        }
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('WhatsApp URL no está configurada');
        }
    }

    public function sendTemplateMessage(
        string $phone,
        string $templateName,
        array $parameters,
        string $languageCode = 'es'
    ): array {
        try {
            // Formatear número de teléfono
            $phoneNumber = $this->formatPhoneNumber($phone);

            // Construir mensaje
            $message = [
                "messaging_product" => "whatsapp",
                "to" => $phoneNumber,
                "type" => "template",
                "template" => [
                    "name" => $templateName,
                    "language" => ["code" => $languageCode],
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => array_map(function ($text) {
                                return [
                                    "type" => "text",
                                    "text" => $text
                                ];
                            }, $parameters)
                        ]
                    ]
                ]
            ];

            // Enviar mensaje
            $response = $this->makeRequest($message);

            return [
                'success' => true,
                'data' => $response
            ];
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de WhatsApp', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber ?? $phone,
                'template' => $templateName
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remover todos los caracteres no numéricos
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // Validar longitud del número (ajusta según tus necesidades)
        if (strlen($clean) < 8) {
            throw new \InvalidArgumentException('Número de teléfono inválido');
        }

        // Agregar prefijo si no existe
        if (!str_starts_with($clean, '591')) {
            $clean = '591' . $clean;
        }

        return '+' . $clean;
    }

    private function makeRequest(array $data): array
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $this->defaultHeaders,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($statusCode !== 200) {
            $error = json_decode($response, true);
            throw new \RuntimeException(
                'Error en WhatsApp API: ' .
                    ($error['error']['message'] ?? 'Error desconocido') .
                    ' (Status: ' . $statusCode . ')'
            );
        }

        if ($error = curl_error($curl)) {
            throw new \RuntimeException('Error de CURL: ' . $error);
        }

        curl_close($curl);

        return json_decode($response, true);
    }
}

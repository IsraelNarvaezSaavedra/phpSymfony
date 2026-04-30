<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IAService
{
    private HttpClientInterface $httpClient;
    private $apiKey;

    private $sysPrompt;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, string $sysPrompt = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->sysPrompt = $sysPrompt;
    }

    public function getsysPrompt(): ?string
    {
        return $this->sysPrompt;
    }

    public function setSysPrompt(string $sysPrompt): static
    {
        $this->sysPrompt = $sysPrompt;

        return $this;
    }

    /*public function getSysPrompt(?string $prompt): ?string{
        return $prompt;
    }*/
    public function generarRespuesta(?string $prompt = null): string
    {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        try {
            $response = $this->httpClient->request(
                'POST',
                $url,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'llama-3.3-70b-versatile',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $this->getSysPrompt()
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'max_tokens' => 800
                    ]

                ]
            );
            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? 'Lo siento, no pude generar una respuesta.';
        } catch (\Exception $e) {
            return "Lo siento, estamos saturados en estos momentos. Por favor, inténtalo de nuevo más tarde. " . $e->getMessage();
        }
    }
}
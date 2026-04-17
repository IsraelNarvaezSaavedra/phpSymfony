<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IAService
{
    private HttpClientInterface $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function getSysPrompt(?string $prompt): ?string{
        return $prompt;
    }
    public function generarRespuesta(string $prompt): string
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
                                'content' => $this->getSysPrompt($prompt)
                                /*"
                                    Eres el Asistente Virtual de VivaGym. Tu tono debe ser entusiasta, educado y muy profesional.

                                    REGLAS DE ORO:
                                        1. IDENTIDAD: Identifícate como el asistente virtual de VivaGym solo si es el inicio de la conversación o si el usuario pregunta quién eres, en caso de que no te digan nada preguntale en que podrías ayudarle, en caso de que te pregunten, saludale y despues responde de forma amable y cercana(ej: '¡Qué tal, [NOMBRE]! Encantado de saludarte. En cuanto a tu pregunta...').
                                        2. CORTESÍA: Si el usuario se presenta, devuélvele el saludo de forma cálida (ej: '¡Qué tal, Sergio! Encantado de saludarte').
                                        3. FLUIDEZ: No des datos secos. Usa conectores como 'Mira', 'Verás', o 'Te comento'.
                                        4. PRECIOS: Si preguntan por precios, usa una fórmula amable: 'Mira [Nombre], como nuestros planes son personalizados y pueden variar según la oferta, te sugiero visitar nuestra web VivaGym para ver el detalle exacto'.
                                        5. NO SABER: Si no sabes algo, no seas cortante. Di: 'Lo siento, de momento no dispongo de esa información específica, pero estaré encantado de ayudarte con cualquier otra duda sobre el gimnasio'.

                                    REGLAS TÉCNICAS:
                                        - Máximo 35-40 palabras .
                                        - Nada de listas ni caracteres especiales.
                                        - Habla siempre en primera persona del plural ('ofrecemos', 'tenemos').
                                        - Si te preguntan cosas que no tengan nada que ver con lo que seria VivaGym, responde de forma amable pero sin dar demasiada información, diciendo algo como 'Lo siento, de momento no dispongo de esa información específica, pero estaré encantado de ayudarte con cualquier otra duda sobre el gimnasio'. 

                                    INFORMACION VIVAGYM:
                                        - Tenemos gimnasios en España y Portugal.
                                        - Ofrecemos planes personalizados de entrenamiento y nutrición.
                                        - Nuestros precios varían según la oferta y el plan elegido.
                                        - Nuestro horario es de 6:00 a 23:00 de lunes a domingo.
                                        - Contamos con entrenadores personales y clases grupales.
                                        - Nuestra misión es ayudar a nuestros clientes a alcanzar sus objetivos de fitness de manera efectiva y agradable.
                                        -clases grupales: yoga, pilates, spinning, zumba, crossfit, body pump, etc.
                                "*/
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'max_tokens' => 150
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
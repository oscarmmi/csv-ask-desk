<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiLlmClient implements LlmClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function proposeFilter(string $question): array
    {
        $response = Http::connectTimeout(3)
            ->timeout(12)
            ->post(sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                rawurlencode($this->model),
                rawurlencode($this->apiKey),
            ), [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => "Translate the user's question into one JSON object only. Allowed keys: status (one of shipped, processing, delivered, cancelled), city (a city name), min_total (a number; use only for over/above questions). Omit unknown filters. Never return SQL or any other keys. Question: ".$question,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0,
                    'responseMimeType' => 'application/json',
                ],
            ])
            ->throw();

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text)) {
            throw new RuntimeException('Gemini returned no filter proposal.');
        }

        $decoded = json_decode(trim($text), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid filter JSON.');
        }

        return $decoded;
    }
}

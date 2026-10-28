<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    private const CITIES = [
        'Austin',
        'Chicago',
        'Denver',
        'Portland',
        'Seattle',
    ];

    public function proposeFilter(string $question): array
    {
        $filter = [];

        if (preg_match('/\b(shipped|processing|delivered|cancelled)\b/i', $question, $status)) {
            $filter['status'] = strtolower($status[1]);
        }

        foreach (self::CITIES as $city) {
            if (preg_match('/\b'.preg_quote($city, '/').'\b/i', $question)) {
                $filter['city'] = $city;
                break;
            }
        }

        if (preg_match('/\b(?:over|above|more than|greater than)\s*\$?\s*(\d+(?:\.\d{1,2})?)/i', $question, $total)) {
            $filter['min_total'] = (float) $total[1];
        }

        return $filter;
    }
}

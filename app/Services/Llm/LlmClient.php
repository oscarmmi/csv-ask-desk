<?php

namespace App\Services\Llm;

interface LlmClient
{
    /**
     * Return a proposed structured filter with the supported keys status, city, and min_total.
     *
     * @return array<string, mixed>
     */
    public function proposeFilter(string $question): array;
}

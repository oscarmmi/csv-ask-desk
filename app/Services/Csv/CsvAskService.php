<?php

namespace App\Services\Csv;

use App\Models\CsvAsk;
use App\Models\CsvOrder;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class CsvAskService
{
    private const STATUSES = ['shipped', 'processing', 'delivered', 'cancelled'];

    public function __construct(private readonly LlmClient $llmClient) {}

    /**
     * @return array{ask: CsvAsk, rows: list<array{id: int, customer: string, city: string, status: string, total: string}>}
     */
    public function ask(string $question): array
    {
        try {
            $proposal = $this->llmClient->proposeFilter($question);
        } catch (Throwable $exception) {
            Log::warning('Filter provider failed; using the local demo interpreter.', [
                'exception' => $exception::class,
            ]);

            $proposal = (new FakeLlmClient)->proposeFilter($question);
        }

        $filter = $this->guardFilter($proposal);

        if ($filter === null) {
            $ask = CsvAsk::query()->create([
                'question' => $question,
                'generated_filter' => [],
                'answer' => 'I could not safely interpret that request. Try a status, city, or minimum order total.',
                'row_count' => 0,
            ]);

            return ['ask' => $ask, 'rows' => []];
        }

        $query = CsvOrder::query()->orderBy('id');

        if (isset($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        if (isset($filter['city'])) {
            $query->where('city', $filter['city']);
        }

        if (isset($filter['min_total'])) {
            $query->where('total', '>', $filter['min_total']);
        }

        $orders = $query->get();
        $count = $orders->count();
        $description = $this->describe($filter);
        $answer = $count === 0
            ? 'No orders match '.($description === '' ? 'that request' : $description).'.'
            : sprintf('%d %s %s.', $count, $count === 1 ? 'order matches' : 'orders match', $description ?: 'the request');

        $ask = CsvAsk::query()->create([
            'question' => $question,
            'generated_filter' => $filter,
            'answer' => $answer,
            'row_count' => $count,
        ]);

        $rows = array_values($orders->map(fn (CsvOrder $order): array => [
            'id' => (int) $order->id,
            'customer' => (string) $order->customer,
            'city' => (string) $order->city,
            'status' => (string) $order->status,
            'total' => (string) $order->total,
        ])->all());

        return ['ask' => $ask, 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $proposal
     * @return array{status?: string, city?: string, min_total?: float}|null
     */
    private function guardFilter(array $proposal): ?array
    {
        if (array_diff(array_keys($proposal), ['status', 'city', 'min_total']) !== []) {
            return null;
        }

        $filter = [];

        if (array_key_exists('status', $proposal)) {
            if (! is_string($proposal['status']) || ! in_array(strtolower($proposal['status']), self::STATUSES, true)) {
                return null;
            }

            $filter['status'] = strtolower($proposal['status']);
        }

        if (array_key_exists('city', $proposal)) {
            if (! is_string($proposal['city'])) {
                return null;
            }

            $city = trim($proposal['city']);

            if ($city === '' || mb_strlen($city) > 80) {
                return null;
            }

            $knownCity = CsvOrder::query()->distinct()->pluck('city')
                ->first(fn (string $knownCity): bool => strcasecmp($knownCity, $city) === 0);

            if (! is_string($knownCity)) {
                return null;
            }

            $filter['city'] = $knownCity;
        }

        if (array_key_exists('min_total', $proposal)) {
            if (! is_int($proposal['min_total']) && ! is_float($proposal['min_total'])) {
                return null;
            }

            $minimum = (float) $proposal['min_total'];

            if (! is_finite($minimum) || $minimum < 0 || $minimum > 1000000) {
                return null;
            }

            $filter['min_total'] = $minimum;
        }

        return $filter;
    }

    /** @param array{status?: string, city?: string, min_total?: float} $filter */
    private function describe(array $filter): string
    {
        $parts = [];

        if (isset($filter['status'])) {
            $parts[] = $filter['status'];
        }

        if (isset($filter['city'])) {
            $parts[] = 'in '.$filter['city'];
        }

        if (isset($filter['min_total'])) {
            $parts[] = 'over $'.number_format($filter['min_total'], 2);
        }

        return implode(' ', $parts);
    }
}

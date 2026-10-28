<?php

use App\Models\CsvAsk;
use App\Services\Csv\CsvAskService;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use Database\Seeders\OrdersSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(OrdersSeeder::class);
});

it('returns only shipped orders in Austin', function (): void {
    $result = app(CsvAskService::class)->ask('shipped orders in Austin');

    expect($result['rows'])->toHaveCount(2)
        ->and(collect($result['rows'])->pluck('id')->all())->toBe([1001, 1006])
        ->and(collect($result['rows'])->every(fn (array $row): bool => $row['status'] === 'shipped' && $row['city'] === 'Austin'
        ))->toBeTrue()
        ->and($result['ask']->generated_filter)->toBe([
            'status' => 'shipped',
            'city' => 'Austin',
        ])
        ->and($result['ask']->row_count)->toBe(2);
});

it('rejects a DROP-like model proposal without executing it', function (): void {
    app()->instance(LlmClient::class, new class implements LlmClient
    {
        public function proposeFilter(string $question): array
        {
            return ['sql' => 'DROP TABLE orders'];
        }
    });

    $result = app(CsvAskService::class)->ask('show every order');

    expect($result['rows'])->toBeEmpty()
        ->and($result['ask']->generated_filter)->toBe([])
        ->and($result['ask']->answer)->toContain('could not safely interpret')
        ->and(DB::table('orders')->count())->toBe(20)
        ->and(CsvAsk::query()->count())->toBe(1);
});

it('binds the local fake when no Gemini key is configured', function (): void {
    config(['services.gemini.api_key' => null]);

    expect(app(LlmClient::class))->toBeInstanceOf(FakeLlmClient::class);
});

it('answers a guest post with matching rows', function (): void {
    $this->postJson('/ask', ['question' => 'orders over 100'])
        ->assertOk()
        ->assertJsonPath('ask.row_count', 12)
        ->assertJsonPath('ask.generated_filter.min_total', 100)
        ->assertJsonCount(12, 'rows');
});

it('falls back safely when the configured LLM provider throws', function (): void {
    app()->instance(LlmClient::class, new class implements LlmClient
    {
        public function proposeFilter(string $question): array
        {
            throw new RuntimeException('Provider unavailable');
        }
    });

    $this->postJson('/ask', ['question' => 'shipped orders in Austin'])
        ->assertOk()
        ->assertJsonPath('ask.row_count', 2)
        ->assertJsonCount(2, 'rows');
});

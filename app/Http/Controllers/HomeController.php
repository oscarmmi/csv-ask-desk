<?php

namespace App\Http\Controllers;

use App\Models\CsvOrder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function __invoke(): Response
    {
        $orders = CsvOrder::query()->orderBy('id')->get();
        $statusCounts = DB::table('orders')
            ->select('status', DB::raw('count(*) as orders_count'))
            ->groupBy('status')
            ->pluck('orders_count', 'status');

        return Inertia::render('Welcome', [
            'orders' => $orders->map(fn (CsvOrder $order): array => [
                'id' => (int) $order->id,
                'customer' => (string) $order->customer,
                'city' => (string) $order->city,
                'status' => (string) $order->status,
                'total' => (string) $order->total,
            ])->all(),
            'orderCount' => $orders->count(),
            'cities' => $orders->pluck('city')->unique()->sort()->values()->all(),
            'statusCounts' => $statusCounts,
            'llmMode' => filled(config('services.gemini.api_key')) ? 'Gemini connected' : 'Demo mode · no API key',
        ]);
    }
}

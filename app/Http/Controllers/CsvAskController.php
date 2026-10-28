<?php

namespace App\Http\Controllers;

use App\Services\Csv\CsvAskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsvAskController
{
    public function store(Request $request, CsvAskService $service): JsonResponse
    {
        $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $result = $service->ask((string) $request->input('question'));

        return response()->json([
            'ask' => [
                'id' => $result['ask']->id,
                'question' => $result['ask']->question,
                'generated_filter' => $result['ask']->generated_filter,
                'answer' => $result['ask']->answer,
                'row_count' => $result['ask']->row_count,
            ],
            'rows' => $result['rows'],
        ]);
    }
}

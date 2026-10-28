<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvAsk extends Model
{
    protected $fillable = [
        'question',
        'generated_filter',
        'answer',
        'row_count',
    ];

    protected function casts(): array
    {
        return [
            'generated_filter' => 'array',
            'row_count' => 'integer',
        ];
    }
}

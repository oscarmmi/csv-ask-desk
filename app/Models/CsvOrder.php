<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsvOrder extends Model
{
    public $timestamps = false;

    protected $table = 'orders';

    protected $fillable = [
        'id',
        'customer',
        'city',
        'status',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }
}

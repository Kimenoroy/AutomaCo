<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'description',
        'is_active',
    ];

    protected function casts()
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}

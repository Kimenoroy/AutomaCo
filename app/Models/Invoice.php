<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'generation_code',
        'control_number',
        'stamp',
        'provider_name',
        'provider_nit',
        'issue_date',
        'taxable_amount',
        'fovial_amount',
        'total_amount',
        'status',
        'pdf_path',
        'json_path',
        'raw_data'
    ];

    /* Convertir automáticamente el JSON a array de PHP y fechas a objetos Carbon */
     protected $casts = [
        'issue_date' => 'datetime',
        'raw_data' => 'array',
        'taxable_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
}

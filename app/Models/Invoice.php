<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'client_name',
        'generation_code',
        'pdf_path',
        'json_path',
        'pdf_original_name',
        'pdf_created_at',
        'json_original_name',
        'json_created_at',
    ];

    /**
     * Relación con usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
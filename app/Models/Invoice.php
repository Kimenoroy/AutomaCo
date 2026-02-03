<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    
 // app/Models/Invoice.php
protected $fillable = [
    'connected_account_id', // <--- Asegúrate que diga esto y NO user_id
    'client_name',
    'generation_code',
    'pdf_path',
    'json_path',
    'pdf_original_name',
    'pdf_created_at',
    'json_original_name',
    'json_created_at',
];

public function connectedAccount()
{
    return $this->belongsTo(ConnectedAccount::class);
}
}
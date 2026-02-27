<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // Esta lista le da permiso a Laravel de guardar estos datos.
    // Si falta 'plan_name' aquí, dará el error de "cannot be null".
    protected $fillable = [
        'email',
        'reference',
        'plan_name',
        'amount',
        'status',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivationCode extends Model
{
    protected $fillable = ['code_hash', 'is_used', 'used_at', 'user_id'];

    protected $casts = ['is_used' => 'boolean', 'used_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    protected $fillable = [
        'email', 'code', 'purpose', 'payload', 'expires_at', 'consumed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'expires_at'   => 'datetime',
        'consumed_at'  => 'datetime',
    ];
}
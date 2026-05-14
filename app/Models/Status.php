<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false; // No timestamps in migration

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
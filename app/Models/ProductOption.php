<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\User;

class ProductOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'option_type',
        'option_name',
        'price',
        'image_url',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
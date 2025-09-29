<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ProductType;

class Product extends Model
{
    use HasFactory;

     protected $fillable = [
        'product_type_id',
        'user_id',
        'name',
        'description',
        'price',
        'stock',
        'img_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\User;
use App\Models\CartItem;
use App\Models\OrderDetail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        // 'user_id',
        'option_type',
        'option_type_kh',
        'option_name',
        'option_name_kh',
        'price',
        'pricing_type',
        'image_url',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

     public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // public function cartItems()
    // {
    //     return $this->belongsToMany(
    //         CartItem::class,
    //         'cart_item_product_option',
    //         'product_option_id',
    //         'cart_item_id'
    //     );
    // }

    // public function orderDetails()
    // {
    //     return $this->belongsToMany(
    //         OrderDetail::class,
    //         'order_detail_product_option',
    //         'product_option_id',
    //         'order_detail_id'
    //     );
    // }
}
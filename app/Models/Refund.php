<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status_id',
        'request_by',
        'reason',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    public $timestamps = false; // Only created_at, no updated_at

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // public function orderDetail()
    // {
    //     return $this->belongsTo(OrderDetail::class, 'order_item_id');
    // }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class, 'refund_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(RefundImage::class, 'refund_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
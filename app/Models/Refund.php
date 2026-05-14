<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Status;
use App\Models\User;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'status_id',
        'request_by',
        'approved_by',
    ];

    public $timestamps = false; // Only created_at, no updated_at

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_item_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'request_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
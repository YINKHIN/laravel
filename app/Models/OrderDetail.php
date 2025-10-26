<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'ord_code',
        'pro_code',
        'pro_name',
        'qty',
        'price',
        'amount',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the order that owns the order detail.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'ord_code');
    }

    /**
     * Get the product that owns the order detail.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'pro_code');
    }
}

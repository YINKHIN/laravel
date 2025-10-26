<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_date',
        'staff_id',
        'full_name',
        'ord_code',
        'total',
        'deposit',
        'remain',
    ];

    protected $casts = [
        'pay_date' => 'datetime',
        'total' => 'decimal:2',
        'deposit' => 'decimal:2',
        'remain' => 'decimal:2',
    ];

    /**
     * Get the staff that owns the payment.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id','id');
    }

    /**
     * Get the order that owns the payment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'ord_code');
    }
}

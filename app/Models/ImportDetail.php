<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'imp_code',
        'pro_code',
        'pro_name',
        'qty',
        'price',
        'amount',
        'batch_number',
        'expiration_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
        'expiration_date' => 'date',
    ];

    /**
     * Get the import that owns the import detail.
     */
    public function import()
    {
        return $this->belongsTo(Import::class, 'imp_code');
    }

    /**
     * Get the product that owns the import detail.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'pro_code');
    }
}

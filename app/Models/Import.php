<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'imp_date',
        'staff_id',
        'full_name',
        'sup_id',
        'supplier',
        'total',
        'amount',
        'qty',
        'batch_number',
        'expiration_date',
        'status',
    ];

    protected $casts = [
        'imp_date' => 'date',
        'amount' => 'decimal:2',
        'qty' => 'integer',
        'expiration_date' => 'date',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['total_quantity', 'products_count'];

    /**
     * Get the staff that owns the import.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the supplier that owns the import.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'sup_id');
    }

    /**
     * Get the import details for the import.
     */
    public function importDetails()
    {
        return $this->hasMany(ImportDetail::class, 'imp_code');
    }

    /**
     * Get the total quantity of all products in this import.
     */
    public function getTotalQuantityAttribute()
    {
        return $this->importDetails()->sum('qty');
    }

    /**
     * Get the count of different products in this import.
     */
    public function getProductsCountAttribute()
    {
        return $this->importDetails()->count();
    }
}

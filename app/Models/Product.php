<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'pro_name',
        'pro_description',
        'category_id',
        'brand_id',
        'upis',
        'sup',
        'qty',
        'image',
        'status',
        'reorder_point',
        'reorder_quantity',
        'batch_number',
        'expiration_date'
    ];

    protected $casts = [
        'qty' => 'integer',
        'upis' => 'decimal:2',
        'sup' => 'decimal:2',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'expiration_date' => 'date'
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['image_url'];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the brand that owns the product.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the full URL for the product image.
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return url('storage/' . $this->image);
        }
        return null;
    }

    /**
     * Check if product needs reordering
     */
    public function needsReordering()
    {
        return $this->qty <= $this->reorder_point;
    }

    /**
     * Check if product is expired
     */
    public function isExpired()
    {
        if (!$this->expiration_date) {
            return false;
        }
        return now()->gt($this->expiration_date);
    }

    /**
     * Check if product is near expiration (within 30 days)
     */
    public function isNearExpiration()
    {
        if (!$this->expiration_date) {
            return false;
        }
        return now()->addDays(30)->gte($this->expiration_date) && now()->lt($this->expiration_date);
    }
}
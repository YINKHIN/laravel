<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $table = "categories";
    public $timestamps = true;
    
    protected $fillable = [
        'name',
        'description',
        'image',
        'status'
    ];

    protected $appends = [
        'image_url'
    ];

    /**
     * Get the full URL for the category image.
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // Check if it's already a full URL (for external images)
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Generate URL for local storage
        return Storage::disk('public')->url($this->image);
    }

    /**
     * Get the products for the category.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to search categories by name or description.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
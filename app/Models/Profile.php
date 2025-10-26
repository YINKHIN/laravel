<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'image',
        'type',
        'verification_code',
        'verification_code_expires_at',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['image_url'];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the profile's type.
     */
    public function getTypeAttribute($value)
    {
        return $value;
    }
    
    /**
     * Set the profile's type.
     */
    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = $value;
    }

    /**
     * Get the full URL for the profile image.
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }
}
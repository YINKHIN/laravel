<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staffs';

    protected $fillable = [
        'full_name',
        'gen',
        'dob',
        'position',
        'salary',
        'stopwork',
        'photo',
        'status',
    ];

    protected $casts = [
        'dob' => 'date',
        'stopwork' => 'boolean',
        'salary' => 'decimal:2',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['photo_url'];

    /**
     * Get the full URL for the staff photo.
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return url('storage/' . $this->photo);
        }
        return null;
    }

    /**
     * Get the imports for the staff.
     */
    public function imports()
    {
        return $this->hasMany(Import::class, 'staff_id');
    }

    /**
     * Get the orders for the staff.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'staff_id');
    }

    /**
     * Get the payments for the staff.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'staff_id','id');
    }
}

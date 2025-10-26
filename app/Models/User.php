<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    
    // Return the unique identifier for the user
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Return an array of custom claims for the JWT
    public function getJWTCustomClaims()
    {
        return [];
    }
    
    /**
     * The column name for the user type.
     *
     * @var string
     */
    // const TYPE_COLUMN = 'user_type';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the profile associated with the user.
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    
    /**
     * Get the user's profile image URL.
     */
    public function getImageUrlAttribute()
    {
        if ($this->profile && $this->profile->image) {
            return asset('storage/' . $this->profile->image);
        }
        return null;
    }
    
    /**
     * Get the user's type.
     */
    public function getTypeAttribute()
    {
        return $this->user_type;
    }
    
    /**
     * Set the user's type.
     */
    public function setTypeAttribute($value)
    {
        $this->attributes['user_type'] = $value;
    }
}
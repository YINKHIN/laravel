<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_url',
        'is_read',
        'read_at',
        'notifiable_type',
        'notifiable_id',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread()
    {
        $this->is_read = false;
        $this->read_at = null;
        $this->save();
        
        return $this;
    }

    /**
     * Determine if a notification has been read.
     */
    public function isRead()
    {
        return $this->is_read;
    }

    /**
     * Determine if a notification has not been read.
     */
    public function isUnread()
    {
        return !$this->is_read;
    }

    /**
     * Scope a query to only include unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to only include read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}
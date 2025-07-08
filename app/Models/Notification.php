<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admin_id',
        'type',
        'message',
        'related_model',
        'related_model_id',
        'is_read',
    ];

    // Relationship to User (for user notifications)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to Admin (for admin notifications)
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}

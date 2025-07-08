<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMediaLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'icon',
        'hover_icon',
        'index_no',
        'status'
    ]; // Allow mass assignment for these fields

    // You can add any custom methods here if needed
}

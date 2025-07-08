<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllowedOrigin extends Model
{
    use HasFactory;

    // Define the table associated with the model (optional, Laravel uses pluralized table names by default)
    protected $table = 'allowed_origins';

    // Define the fillable fields
    protected $fillable = ['origin_url'];
}

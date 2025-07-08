<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AmadeusToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'username',
        'application_name',
        'client_id',
        'token_type',
        'access_token',
        'expires_in',
        'state',
        'scope',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}

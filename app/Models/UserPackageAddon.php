<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPackageAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'addon_id',
        'purchase_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function addon()
    {
        return $this->belongsTo(PackageAddon::class);
    }

    // Add the relationship to UserPackage (many UserPackageAddons belong to one UserPackage)
    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class, 'purchase_id', 'id');
    }
}

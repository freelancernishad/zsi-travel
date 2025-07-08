<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponAssociation extends Model
{
    use HasFactory;

    protected $fillable = ['coupon_id', 'item_id', 'item_type'];

    // Define inverse relationships for each type
    public function user()
    {
        return $this->belongsTo(User::class, 'item_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'item_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'item_id');
    }
}

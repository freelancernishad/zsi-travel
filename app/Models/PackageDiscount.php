<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageDiscount extends Model
{
    protected $fillable = ['package_id', 'duration_months', 'discount_rate'];

    /**
     * Relationship to the Package model.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}

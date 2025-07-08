<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'valid_from', 'valid_until', 'is_active', 'usage_limit'
    ];



    // Relationship with coupon associations (many-to-many)
    public function associations()
    {
        return $this->hasMany(CouponAssociation::class);
    }

    /**
     * Get all the usages of this coupon.
     */
    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if the coupon is valid.
     */
    public function isValid()
    {
        $now = now();

        return $this->is_active && ($this->valid_from <= $now && $this->valid_until >= $now);
    }

    /**
     * Check if coupon usage limit is exceeded.
     */
    public function hasUsageLimit()
    {
        return $this->usage_limit && $this->usages->count() >= $this->usage_limit;
    }

    /**
     * Get the discount amount based on the coupon type.
     */
    public function getDiscountAmount($amount)
    {
        if ($this->type == 'percentage') {
            return ($this->value / 100) * $amount;
        }

        return $this->value; // Fixed discount value
    }
}

<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'gateway', 'session_id','transaction_id', 'currency', 'amount', 'fee',
        'status', 'response_data', 'payment_method', 'payer_email', 'paid_at','coupon_id','payable_type','payable_id','user_package_id', 'business_name'
    ];

    protected $casts = [
        'response_data' => 'array', // Cast JSON data to an array
        'paid_at' => 'datetime', // Cast as a datetime
    ];

    // Define relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function couponUsage()
    {
        return $this->hasOne(CouponUsage::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function userPackage()
    {
        return $this->belongsTo(UserPackage::class);
    }


        /**
     * Scope for completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for refunded payments.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope for payments with discounts.
     */
    public function scopeDiscounted($query)
    {
        return $query->whereNotNull('coupon_id');
    }

    /**
     * Scope for payments by gateway.
     */
    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope for payments by service or package.
     */
    public function scopeForPayable($query, $payableType, $payableId)
    {
        return $query->where('payable_type', $payableType)->where('payable_id', $payableId);
    }



    protected static function boot()
    {
        parent::boot();

        // Generate a unique transaction_id before creating the payment
        static::creating(function ($payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = static::generateUniqueTransactionId();
            }
        });
    }

    /**
     * Generate a unique transaction ID.
     *
     * @return string
     */
    public static function generateUniqueTransactionId()
    {
        $prefix = 'txn_'; // Prefix for the transaction ID
        $timestamp = now()->format('YmdHis'); // Current date and time in YYYYMMDDHHMMSS format
        $randomString = Str::random(3); // Random alphanumeric string of 6 characters

        // Combine prefix, timestamp, and random string
        $transactionId = $prefix . $timestamp . '_' . $randomString;

        return $transactionId;
    }



}

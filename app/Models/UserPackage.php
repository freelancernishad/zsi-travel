<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Carbon\Carbon;

class UserPackage extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'started_at',
        'ends_at',
        'business_name',
        'stripe_subscription_id', // Add Stripe subscription ID
        'stripe_customer_id', // Add Stripe customer ID
        'status', // Add subscription status (active, canceled, expired)
        'canceled_at', // Add canceled_at timestamp
        'next_billing_at', // Add next billing date

        'payment_method_type',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'bank_name',
        'iban_last_four',
        'account_holder_type',
        'account_last_four',
        'routing_number',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'next_billing_at' => 'datetime',
    ];

    /**
     * Relationship: A UserPackage belongs to a User.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A UserPackage belongs to a Package.
     *
     * @return BelongsTo
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Relationship: A UserPackage has many UserPackageDocuments.
     *
     * @return HasMany
     */
    public function documents(): HasMany
    {
        return $this->hasMany(UserPackageDocument::class, 'userpackage_id');
    }

    /**
     * Relationship: A UserPackage has many UserPackageAddons.
     *
     * @return HasMany
     */
    public function addons(): HasMany
    {
        return $this->hasMany(UserPackageAddon::class, 'purchase_id', 'id');
    }

    /**
     * Relationship: A UserPackage has many Addons through UserPackageAddon.
     *
     * @return HasManyThrough
     */
    public function addonsDetails(): HasManyThrough
    {
        return $this->hasManyThrough(
            PackageAddon::class,
            UserPackageAddon::class,
            'purchase_id', // Foreign key on UserPackageAddon to UserPackage
            'id', // Foreign key on PackageAddon to be matched
            'id', // Local key on UserPackage to be matched
            'addon_id' // Foreign key in UserPackageAddon for PackageAddon
        );
    }

    /**
     * Relationship: A UserPackage has one Payment.
     *
     * @return HasOne
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get formatted package details.
     *
     * @return array
     */
    public function getFormattedDetails(): array
    {
        return [
            'id' => $this->id,
            'package_name' => $this->package->name ?? 'N/A',
            'plan' => $this->getPlanType(), // Monthly or Yearly
            'active_date' => $this->started_at->toDateString(),
            'end_date' => $this->ends_at->toDateString(),
            'status' => $this->getStatus(), // Active, Expired, or Canceled
            'next_billing_date' => $this->next_billing_at?->toDateString() ?? 'N/A', // Next billing date
        ];
    }

    /**
     * Determine the plan type (monthly/yearly) based on the package duration.
     *
     * @return string
     */
    protected function getPlanType(): string
    {
        $durationInMonths = $this->started_at->diffInMonths($this->ends_at);

        return $durationInMonths >= 12 ? 'yearly' : 'monthly';
    }

    /**
     * Determine the status of the package (active/expired/canceled).
     *
     * @return string
     */
    protected function getStatus(): string
    {
        $now = now();

        if ($this->status === 'canceled') {
            return 'canceled';
        }

        if ($this->started_at <= $now && $this->ends_at >= $now) {
            return 'active';
        }

        return 'expired';
    }

    /**
     * Get active packages for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActivePackages(int $userId)
    {
        return self::where('user_id', $userId)
            ->where('started_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where('status', 'active') // Only active subscriptions
            ->with('package') // Eager load package details
            ->get()
            ->map(function ($userPackage) {
                return $userPackage->getFormattedDetails();
            });
    }

    /**
     * Get package history for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPackageHistory(int $userId)
    {
        return self::where('user_id', $userId)
            ->with('package') // Eager load package details
            ->orderBy('started_at', 'desc') // Order by start date (most recent first)
            ->get()
            ->map(function ($userPackage) {
                return $userPackage->getFormattedDetails();
            });
    }

    /**
     * Cancel the subscription.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }

    /**
     * Reactivate the subscription.
     *
     * @return void
     */
    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'canceled_at' => null,
        ]);
    }

    /**
     * Update the next billing date.
     *
     * @param Carbon $nextBillingAt
     * @return void
     */
    public function updateNextBillingDate(Carbon $nextBillingAt): void
    {
        $this->update([
            'next_billing_at' => $nextBillingAt,
        ]);
    }
}

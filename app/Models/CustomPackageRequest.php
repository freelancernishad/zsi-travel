<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPackageRequest extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'business',
        'phone',
        'website',
        'service_description',
        'status',
        'admin_notes',
        'package_id',
    ];

    // Default status for new requests
    protected $attributes = [
        'status' => 'pending', // Default status
    ];

    // Status options
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Get the status options for the request.
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

        /**
     * Relationship to the Package model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}

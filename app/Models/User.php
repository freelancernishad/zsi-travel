<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'stripe_customer_id',
        'profile_picture',
        'password',
        'email_verified_at',
        'email_verification_hash',
        'otp',
        'otp_expires_at',



        'phone',
        'business_name',
        'country',
        'state',
        'city',
        'region',
        'zip_code',
        'stripe_customer_id'


    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_hash',
        'email_verified_at',
        'otp',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => !is_null($this->email_verified_at),
        ];
    }


    public function userPackage()
    {
        return $this->hasOne(UserPackage::class);
    }

    public function userPackagePackagesHistory()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function currentPackage()
    {
        return $this->userPackage ? $this->userPackage->package : null;
    }

    public function hasFeature($feature)
    {
        $package = $this->currentPackage();
        return $package && in_array($feature, $package->features);
    }


    public function saveProfilePicture($file)
    {
        $filePath = uploadFileToS3($file, 'profile_pictures'); // Define the S3 directory
        $this->profile_picture = $filePath;
        $this->save();

        return $filePath;
    }

    /**
     * Get all schedules created by the user.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }


    public function getBusinessNameAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setBusinessNameAttribute($value)
    {
        $this->attributes['business_name'] = $value;
    }

}

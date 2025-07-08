<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reply extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'admin_id',
        'user_id',
        'reply',
        'reply_id',
        'attachment', // Add this line
    ];

    protected $with = [
        'admin',
        'user',
    ];


    // The parent reply relationship
    public function parent()
    {
        return $this->belongsTo(Reply::class, 'reply_id');
    }

    // The children replies (nested replies)
    public function children()
    {
        return $this->hasMany(Reply::class, 'reply_id');
    }

    public function supportTicket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class); // Assuming you have an Admin model
    }
    // Relationship with the User
    public function user()
    {
        return $this->belongsTo(User::class); // Assuming you have a User model
    }


    /**
     * Save the attachment for the reply.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string File path of the uploaded attachment
     */
    public function saveAttachment($file)
    {
        $filePath = uploadFileToS3($file, 'attachments/replies'); // Define the S3 directory
        $this->attachment = $filePath;
        $this->save();

        return $filePath;
    }
}

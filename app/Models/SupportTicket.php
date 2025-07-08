<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'status',
        'priority', // Add this line
        'attachment', // Add this line
    ];



    protected $with = [
        'user',
        'replies',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }



    /**
     * Save the attachment for the support ticket.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string File path of the uploaded attachment
     */
    public function saveAttachment($file)
    {
        $filePath = uploadFileToS3($file, 'attachments/support_tickets'); // Define the S3 directory
        $this->attachment = $filePath;
        $this->save();

        return $filePath;
    }

}

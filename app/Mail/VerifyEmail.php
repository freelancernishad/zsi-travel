<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailable;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;
    protected $verify_url;

    /**
     * Create a new notification instance.
     *
     * @param  \App\Models\User  $user
     * @param  string  $verify_url
     * @return void
     */
    public function __construct($user, $verify_url)
    {
        $this->user = $user;
        $this->verify_url = $verify_url;
    }



    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */

     public function build()
     {
         return $this->from(config('mail.from.address'), config('mail.from.name'))
                     ->subject('Email Verification Required')
                     ->view('emails.verify')
                     ->with([
                         'user' => $this->user,
                         'verify_url' => $this->verify_url,
                     ]);
     }
}

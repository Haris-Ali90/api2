<?php

namespace App\Notifications\Api;

use App\Classes\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserCreateNotification extends Notification
{
    use Queueable;



    /**
     * Create a new notification instance.
     * @param string $status
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable) : array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable) : MailMessage
    {
        $email = base64_encode($notifiable->email);
        $mailMessage = (new MailMessage)->greeting($notifiable->full_name);

        $mailMessage = (new MailMessage)->greeting('Hello, ' . $notifiable->full_name);
        $mailMessage = $mailMessage->subject(Email::makeSubject('Successfully Registered'));
        $mailMessage = $mailMessage->line('Welcome to JoeyCo!');
        $mailMessage = $mailMessage->line('To activate your account and verify your email address, please click the button below:');
        $mailMessage = $mailMessage->action('Activate My Account',route('account.active',[$email,$notifiable->email_verify_token]));
        $mailMessage = $mailMessage->line('Thank you for using our application!');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable) : array
    {
        return [
            //
        ];
    }
}

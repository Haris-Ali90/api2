<?php

namespace App\Notifications\Backend;

use App\Classes\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPasswordNotification extends Notification
{
    use Queueable;

    public $email;

    public $token;

    public $role_id;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($email,$token)
    {
        $this->email = $email;
        $this->token = $token;
//        $this->role_id = $role_id;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
      //   ->action('Reset Password', route('password.reset', [$this->email,$this->token]))
        return (new MailMessage)
            ->subject(Email::makeSubject('Reset Password Link'))
            ->line('You are receiving this email from JoeyCo Joey portal,Kindly reset your password and login to your account.')
            ->line('To reset your password.Click on the button below.')
            ->action('Reset Password', 'https://joey.joeyco.com/password/reset/' .$this->email. '/' .$this->token .'/6')
            ->line('If you did not request a password reset, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

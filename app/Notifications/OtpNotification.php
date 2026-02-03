<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    protected $otp;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp, string $type)
    {
        $this->otp = $otp;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $action = $this->type === 'registration' ? 'Registration' : 'Login';
        
        return (new MailMessage)
            ->subject("Your OTP for {$action}")
            ->greeting("Hello!")
            ->line("Your OTP for {$action} is:")
            ->line("**{$this->otp}**")
            ->line("This OTP will expire in 10 minutes.")
            ->line("If you did not request this OTP, please ignore this email.")
            ->salutation('Regards, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
            'type' => $this->type,
        ];
    }
}

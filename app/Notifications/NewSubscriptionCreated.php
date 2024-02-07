<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubscriptionCreated extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $subscription_id;

    /**
     * Create a new notification instance.
     */
    public function __construct($subscription_id)
    {
        $this->subscription_id = $subscription_id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Thank you for subscribing')
            ->markdown('emails.invoice', [
                'notifiable' => $notifiable
            ]);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'user_id' => $notifiable->id,
            'subscription_id' => $this->subscription_id,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $notifiable->id,
            'subscription_id' => $this->subscription_id
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationLinkNotification extends Mailable
{
    use Queueable;

    public $invitation, $subject;

    /**
     * @param $invitation
     * @param $subject
     */
    public function __construct($invitation, $subject)
    {
        $this->invitation = $invitation;
        $this->subject = $subject;
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
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello ' . $this->invitation->firstname)
            ->line('Please use this link to register yourself')
            ->line(config('app.url')."/member/invitation?invitation".encrypt($this->invitation->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

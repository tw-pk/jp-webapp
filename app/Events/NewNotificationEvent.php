<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NewNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;
    public $data;
    /**
     * Create a new event instance.
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    public function broadcastWith(): array
    {

        return [
            "id" => Carbon::now()->timestamp,
            //"img" => 'paypal',
            "text" => $this->data['text'] ?? '',
            "title" => $this->data['title'] ?? '',
            "subtitle" => $this->data['subtitle'] ?? '',
            "time" => Carbon::now()->setTimezone('Asia/Karachi')->format('H:i:s l, F j, Y'),
            "isSeen" => false,
            "color" => $this->data['color'] ?? '',
        ];
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('User.' . $this->user_id);
    }

    public function broadcastAs(): string
    {
        return "NewNotificationEvent";
    }
}

<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\UserNumber;
use App\Notifications\NewMessageArrived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messagePayload;

    /**
     * Create a new job instance.
     */
    public function __construct($messagePayload)
    {
        $this->messagePayload = $messagePayload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messagePayload = $this->messagePayload;

        $to = $messagePayload['To'];
        $from = $messagePayload['From'];

        $user_numbers = UserNumber::where('phone_number', $to)->get();

        if($user_numbers){


            foreach ($user_numbers as $user_number) {

                $contact = $user_number->user?->contacts?->where('phone', $from)->first();

                $fromName = '';
                $contact_id = null;

                if($contact){
                    $fromName = $contact->fullName();
                    $contact_id = $contact->id;
                }

                $conversation = new Conversation();
                $conversation->user_id = $user_number->user_id;
                $conversation->contact_id = $contact_id;
                $conversation->from = $messagePayload['From'];
                $conversation->to = $messagePayload['To'];
                $conversation->direction = 'incoming';
                $conversation->save();

                $message = new Message();
                $message->from = $from;
                $message->to = $to;
                $message->friendly_name = $fromName;
                $message->fromCity = $messagePayload['FromCity'];
                $message->fromCountry = $messagePayload['FromCountry'];
                $message->fromZip = $messagePayload['FromZip'];
                $message->fromState = $messagePayload['FromState'];
                $message->toCity = $messagePayload['ToCity'];
                $message->toState = $messagePayload['ToState'];
                $message->toCountry = $messagePayload['ToCountry'];
                $message->toZip = $messagePayload['ToZip'];
                $message->smsSId = $messagePayload['SmsSid'];
                $message->smsMessageSId = $messagePayload['SmsMessageSid'];
                $message->status = $messagePayload['SmsStatus'];
                $message->direction = 'incoming';
                $message->media = $messagePayload['NumMedia'];
                $message->body = $messagePayload['Body'];
                $message->conversation_id = $conversation->id;
                $message->save();

                $user_number->user->notify(new NewMessageArrived([
                    'message' => $messagePayload['Body'],
                    'from' => $messagePayload['From'],
                    'to' => $messagePayload['To'],
                    'status' => $message->status
                ]));
            }
        }
    }
}

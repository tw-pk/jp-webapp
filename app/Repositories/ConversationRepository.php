<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Models\Invitation;
use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Random\RandomException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class ConversationRepository
{
    protected $twilioClient;

    public function __construct(Client $twilioClient)
    {
        $this->twilioClient = $twilioClient;
    }

    /**
     * @throws TwilioException
     * @throws RandomException
     */
    public function getConversations($query, $perPage=10): array
    {
        $user = Auth::user();
        $roleName = $user->getRoleNames()->first();
        $contacts = [];
        if($roleName =='Admin'){
            $contacts = $user->contacts->map(function ($contact) {
                $contact->fullName = $contact->firstname . ' ' . $contact->lastname;
                return $contact;
            })
            ->sortByDesc('id');
        }else{
            $ownerId = $user->invitationsMember()->pluck('user_id')->first();
            $contacts = Contact::where('user_id', $ownerId)->get();
            $contacts->transform(function ($contact) {
                $contact->fullName = $contact->firstname . ' ' . $contact->lastname; 
                return $contact;
            });
        }
        
        $chats = [];
        $filteredContacts = [];
        
        if ($query) {
            $conversations = $user->conversations()
                ->whereHas('contact', function ($q) use ($query) {
                    $q->whereRaw("CONCAT(firstname, ' ', lastname) = ?", [$query])
                        ->orWhere('phone_number', 'iLIKE', '%' . $query . '%');
                })->get();

            $contacts = $contacts->whereRaw("CONCAT(firstname, ' ', lastname) = ?", [$query])
                ->orWhere('phone_number', 'iLIKE', '%' . $query . '%');
        }

        $conversations = $user->conversations;
        
        // Count unread messages
        $unreadCount = 0;
        $chat = [];
        
        foreach ($conversations as $conversation) {
            if ($conversation->messages->count()) {
                $lastMessage = $conversation->messages()->orderBy('created_at', 'desc')->first();

                if($lastMessage->status !== 'delivered'){
                    ++$unreadCount;
                }

                $senderId = null;
                if ($lastMessage->direction === 'incoming') {
                    // Do something when the condition is met
                    $contactId = $user->contacts->where('phone', $lastMessage->from)->first()?->id;
                    if($contactId){
                        $senderId = $contactId;
                    }else{
                        $senderId = \Str::random('6');
                    }
                }else{
                    $senderId = $user->id;
                }

                $chat = [
                    'id' => $lastMessage->id,
                    'lastMessage' => [
                        'message' => $lastMessage->body,
                        "time" => Carbon::parse($lastMessage->created_at)->setTimezone('Asia/Karachi')->toDateTimeString(),
                        "senderId" => $senderId,
                        "feedback" => [
                            "isSent" => is_null($lastMessage->status) || $lastMessage->status !== "failed" && $lastMessage->status !== "delivered"  ?? false,
                            "isDelivered" => is_null($lastMessage->status) ||  $lastMessage->status === "delivered" ?? false,
                            "isSeen" => true
                        ]
                    ]
                ];
            }

            $chats[] = [
                'id' => $conversation->id,
                'phone_number' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact?->phone,
                'contact_id' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact_id,
                'fullName' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact?->fullName(),
                'chat' => $chat,
                'role' => 'contact',
                'about' => "",
                'avatar' => '',
                'status' => '',
                'unseenMsgs' => $unreadCount,
            ];
        }

        return [
            'chatContacts' => $chats,
            'contacts' => $contacts,
            'profileUser' => [
                'id' => $user->id,
                'avatar' => $user->profile?->avatar,
                'fullName' => $user->fullName(),
                'role' => $user->getRoleNames()->first(),
                'about' => $user->profile?->bio,
                'status' => 'online',
                'settings' => [
                    'isTwoStepAuthVerificationEnabled' => $user->twoFactorProfile?->enabled,
                    'isNotificationsOn' => true,
                ]
            ]
        ];

    }


    public function create($data)
    {
        // Use Twilio REST client to create a conversation
        $conversation = $this->twilioClient->conversations
            ->conversations
            ->create([
                'friendlyName' => $data['friendlyName'],
                // Additional conversation creation parameters
            ]);

        // Save conversation details to your database or return as needed
        // For example, you might save $conversation->sid to your database

        return $conversation;
    }


    public function getConversationMessages($contactId, $perPage=10)
    {
        $conversation = Auth::user()->conversations->where('contact_id', $contactId)->first();
        if(!$conversation){
            $contact = Auth::user()->contacts->where('id', $contactId)->first();
            return [
                'chat' => "",
                'contact' => [
                    'id' => $contact->id,
                    'phone_number' => $contact->phone,
                    'fullName' => $contact?->fullName(),
                    'about' => $contact->address_home,
                    'role' => 'contact',
                    'avatar' => '',
                    'status' => 'online',
                ],
            ];
        }

        $chat_messages = [];

        if ($conversation->messages->count()) {
            foreach ($conversation->messages as $message) {

                $senderId = null;

                if ($message->direction === 'incoming') {
                    // Do something when the condition is met
                    $contactId = Auth::user()->contacts->where('phone', $message->from)->first()?->id;
                    if($contactId){
                        $senderId = $contactId;
                    }else{
                        $senderId = \Str::random('6');
                    }
                }else{
                    $senderId = Auth::user()->id;
                }

                $chat_messages[] = [
                    'id' => $message->index,
                    'message' => $message->body,
                    "time" => Carbon::parse($message->created_at)->setTimezone('Asia/Karachi')->toDateTimeString(),
                    "senderId" => $senderId,
                    "feedback" => [
                        "isSent" => is_null($message->status) || $message->status !== "failed" && $message->status !== "delivered"  ?? false,
                        "isDelivered" => is_null($message->status) ||  $message->status === "delivered" ?? false,
                        "isSeen" => true
                    ]
                ];
            }
        }

        $chat = [
            'id' => $conversation->id,
            'userId' => Auth::user()->id,
            'phone_number' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact?->phone,
            'contact_id' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact_id,
            'fullName' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact?->fullName(),
            'messages' => $chat_messages,
            'role' => 'contact',
            'about' => "",
            'avatar' => '',
            'status' => '',
        ];

        return [
            'chat' => $chat,
            'contact' => [
                'id' => $conversation->id,
                'phone_number' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact?->phone,
                'fullName' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact?->fullName(),
                'about' => $conversation->direction === 'incoming' ? $conversation->from : $conversation->contact?->phone,
                'role' => 'contact',
                'avatar' => '',
                'status' => 'online',
            ],
        ];
    }


    public function deleteConversation($conversationSId)
    {

    }


    public function updateConversation($conversationSId)
    {

    }




}

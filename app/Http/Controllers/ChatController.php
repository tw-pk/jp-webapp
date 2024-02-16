<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessIncomingMessage;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Services\MessagingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Random\RandomException;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class ChatController extends Controller
{
    protected MessagingService $messagingService;

    /**
     * @param MessagingService $messagingService
     */
    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }


    /**
     * @throws TwilioException|RandomException
     */
    public function fetch_chats_and_contacts(Request $request){
       return $this->messagingService->allConversations($request->q ?? null, $request->perPage ?? null);
    }

    public function fetch_chat($contactId){
        return $this->messagingService->getConversationMessages($contactId);
    }


    public function send_message(Request $request){
        $request->validate([
            'message' => 'required',
            'conversationId' => 'sometimes',
        ]);
        
        $conversation = Conversation::where('id',$request->conversationId)->first();
        if(!$conversation){
            $conversationCreated = $this->createConversation($request->contactId);
            if($conversationCreated){
                $conversation = Conversation::where('contact_id', $request->contactId)->first();
                $this->createParticipant($conversation);
            }

            //create twilio message here
            $messageResponse = $this->twilio->conversations->v1->conversations($conversation->sid)
                ->messages->create([
                    'author' => Auth::user()->fullName(),
                    'body' => $request->message,
                    'attributes' => json_encode([
                        'senderId' => Auth::user()->id,
                        'recipientId' => $conversation->contact_id,
                        'senderPhone' => Auth::user()->numbers?->first()?->phone_number,
                        'receiverPhone' => $conversation->contact->phone,
                        'importance' => 'high'
                    ])
                ]);


            $messageData = [
                'message' => $request->message,
                'time' => Carbon::createFromDate($messageResponse->dateCreated)->setTimezone('Asia/Karachi')->format('d M, Y h:i A'),
                'senderId' => Auth::user()->id,
                'feedback' => [
                    'sent' => $messageResponse->delivery['sent'] ?? false,
                    'delivered' => $messageResponse->delivery['delivered'] ?? false,
                    'read' => $messageResponse->delivery['read'] ?? false
                ],
            ];

            return response()->json([
                'status' => true,
                'msg' => $messageData,
                'chat' => 'undefined'
            ]);
        }
    
        //create twilio message here
        $messageResponse = $this->twilio->conversations->v1->conversations($conversation->sid)
            ->messages->create([
                'author' => Auth::user()->fullName(),
                'body' => $request->message,
                'attributes' => json_encode([
                    'senderId' => Auth::user()->id,
                    'recipientId' => $conversation->contact_id,
                    'senderPhone' => Auth::user()->numbers?->first()?->phone_number,
                    'receiverPhone' => $conversation->contact->phone,
                    'importance' => 'high'
                ])
            ]);


        $messageData = [
            'message' => $request->message,
            'time' => Carbon::createFromDate($messageResponse->dateCreated)->setTimezone('Asia/Karachi')->format('d M, Y h:i A'),
            'senderId' => Auth::user()->id,
            'feedback' => [
                'sent' => $messageResponse->delivery['sent'] ?? false,
                'delivered' => $messageResponse->delivery['delivered'] ?? false,
                'read' => $messageResponse->delivery['read'] ?? false
            ],
        ];

        return response()->json([
            'status' => true,
            'msg' => $messageData,
            'chat' => 'undefined'
        ]);
    }

    private function createConversation($contactId){
       
        $twilio_conversation = $this->twilio->conversations->v1->conversations->create([
            'friendlyName' => Contact::find($contactId)->fullName(),
            'uniqueName' => Contact::find($contactId)->phone
        ]);
        
       $conversation = Conversation::create([
            'sid' => $twilio_conversation->sid,
            'user_id' => Auth::user()->id,
            'friendly_name' => $twilio_conversation->friendlyName,
            'unique_name' => $twilio_conversation->uniqueName,
            'contact_id' => $contactId
        ]);

       if($conversation){
           return true;
       }else{
           return false;
       }
    }

    /**
     * @throws TwilioException
     */
    private function createParticipant($conversation){
        $participant = $this->twilio->conversations->v1->conversations($conversation->sid)
            ->participants
            ->create([
                    "messagingBindingAddress" => $conversation->contact->phone,
                    "messagingBindingProxyAddress" => Auth::user()->numbers->where('active', true)->first()->phone_number
                ]
        );

        ConversationParticipant::create([
            'user_id' => Auth::user()->id,
            'contact_id' => $conversation->contact_id,
            'sid' => $participant->sid
        ]);

    }


    public function handleIncomingMessage(Request $request)
    {
        dispatch(new ProcessIncomingMessage($request->all()));
    }
}

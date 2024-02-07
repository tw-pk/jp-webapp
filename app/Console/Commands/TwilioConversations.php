<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:twilio-conversations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function handle()
    {
        $this->fetchConversations();
    }

    /**
     * @throws ConfigurationException
     */
    public function fetchConversations()
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $authToken = config('app.TWILIO_AUTH_TOKEN');

        $twilio = new Client($sid, $authToken);

        $conversations = $twilio->conversations->v1->conversations->read([], 20);


       $contact = Contact::create([
            'user_id' => 1,
            'firstname' => 'Hamaad',
            'lastname' => 'Kaleem',
            'phone_number' => '+923084920401',
            'email' => 'hamaad@teamswork.pk',
            'home_address' => 'pata nhin',
            'office_address' => 'home_address'
        ]);

        foreach ($conversations as $conversation) {

            if(!Conversation::where('sid', $conversation->sid)->first()){
                $conversationModel = new Conversation();
                $conversationModel->user_id = 1;
                $conversationModel->contact_id = $contact['id'];
                $conversationModel->friendly_name = $conversation->friendlyName;
                $conversationModel->unique_name = $conversation->uniqueName;
                $conversationModel->sid = $conversation->sid;
                $conversationModel->save();
            }
        }
    }
}

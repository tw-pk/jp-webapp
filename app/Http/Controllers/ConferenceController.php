<?php

namespace App\Http\Controllers;

use Twilio\Rest\Client;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Str;
use App\Models\Call;


class ConferenceController extends Controller
{
    protected $twilio;

    public function __construct()
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $this->twilio = new Client($sid, $token);
    }


    public function joinConference(Request $request)
    {
        $response = new VoiceResponse();
        $dial = $response->dial();
        $conferenceName = $request->input('conferenceName');        

        $conference = $dial->conference($conferenceName, [
            'beep' => false,                 
            'endConferenceOnExit' => true
        ]);        

        return response($response)->header('Content-Type', 'text/xml');        
    }

    public function createConference(Request $request)
    {        
        
        $numbers = json_decode($request->input('numbers'));
        $from = $request->input('from');                          
        $callSid = $request->input('callSid');                          
        $to = $request->input('to');               
        
        $conferenceName = 'JotPhoneConference_' . time(); 

        $dialCall = $this->twilio->calls->read([
            'to' => $to,
            'status' => 'in-progress'
        ]);           
        
        $url = route('join-conference', ['conferenceName' => $conferenceName]);

        if (!empty($dialCall)) {            
            foreach ($dialCall as $call) {                
                $childCallSid = $call->sid; 
                $this->twilio->calls($childCallSid)->update([
                    'url' => $url,
                    'method' => 'POST',                    
                ]);   
                
                
                Call::create([
                    'call_sid' => $childCallSid,
                    'conference_sid' => $conferenceName,
                    'participant_number' => $call->to, 
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                                
            }

        } else {
            echo "No in-progress calls found for the number $to.\n";
        }

        if(!empty($callSid)){
            $this->twilio->calls($callSid)->update([
                'url' => $url,
                'method' => 'POST',                    
            ]);      
        }
                   
        
        foreach ($numbers as $number) {
            try {                
                $this->twilio->calls->create(
                    $number,
                    $from,
                    [
                        'url' => $url,
                        'method' => 'POST',                    
                    ]
                );                
                    
            } catch (\Exception $e) {                
                return response()->json(['message' => $e->getMessage()]);
            }
        }                
        
                           
        return response()->json(['message' => 'Conference created and participants invited']);
    }


    public function conferenceStatusCallback(Request $request)
    {

        \Log::info("here is conferece call back =>". $request);
        $conferenceSid = $request->input('ConferenceSid');
        $event = $request->input('StatusCallbackEvent');
        
        if ($event === 'participant-leave') {            
            $participants = $this->twilio->conferences($conferenceSid)
                                        ->participants
                                        ->read();

            \Log::info("here is participants =>". $participants);
            
            if (count($participants) == 1) {                
                $remainingParticipant = $participants[0]->callSid;
                $this->twilio->calls($remainingParticipant)->update(['status' => 'completed']);
            }
        }
        
        return response()->json(['status' => 'ok']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use DB;
use Illuminate\Support\Facades\Http;
use App\Models\UserCredit;
use App\Models\Call;
use App\Models\User;


class CallController extends Controller
{
    protected $twilio;

    public function __construct()
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $this->twilio = new Client($sid, $token);        
    }


    public function placeOnHold(Request $request)
    {        

        $to = $request->input('to');
        $from = $request->input('From');        
        $dialCall = $this->twilio->calls->read([
            'to' => $to,
            'status' => 'in-progress'
        ]);                

        // Check if there are any in-progress calls
        if (!empty($dialCall)) {
            // Loop through each call instance (there could be multiple in-progress calls)
            foreach ($dialCall as $call) {
                // Get the CallSid of the call
                $callSid = $call->sid; 

                $response  = $this->twilio->calls($callSid)->update([
                    'url' => route('hold-url'),
                    'method' => 'POST',                    
                ]);

                return response()->json([
                    'childCallSid' => $callSid
                ]);                                
            }

        } else {
            echo "No in-progress calls found for the number $to.\n";
        }
                   
    }



    public function holdTwiML(Request $request)
    {        
        $response = new VoiceResponse();
        $response->play('https://com.twilio.music.classical.s3.amazonaws.com/BusyStrings.mp3');
        return response($response)->header('Content-Type', 'text/xml');
    }

    public function resumeCall(Request $request)
    {        
                        
        $userCallSid = $request->input('childCallSid');
        $dialerCallSid = $request->input('callSid');
     
        try {            
            $this->twilio->calls($userCallSid)
                ->update([
                    'url' => route('continue-conversation'),
                    'method' => 'POST'
                ]);
            
            $this->twilio->calls($dialerCallSid)
                ->update([
                    'url' => route('continue-conversation'),
                    'method' => 'POST'
                ]);

            return response()->json([
                'message'       => 'Call resumed successfully',
            ]);
        
        } catch (\Exception $e) {
            \Log::error("here is exception coming =>". $e->getMessage());
        }
    }


    /**
     * Generate TwiML to continue the conversation.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function continueConversation(Request $request)
    {        
        $response = new VoiceResponse();
        $dial = $response->dial();
        $conference = $dial->conference('SupportConferenceRoom', [
            'beep' => false,                 
            // 'endConferenceOnExit' => true
        ]);        

        return response($response)->header('Content-Type', 'text/xml');

    }


}

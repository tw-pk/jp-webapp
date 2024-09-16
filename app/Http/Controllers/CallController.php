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


    public function getCallInfo(Request $request) 
    {                               
        return response()->json(['message' => 'Call completed, total price updated'], 200);    
    }


    public function callDisconnected(Request $request)
    {
        \Log::info("number =>". $request->input('to'));
        $to = $request->input('to');      

        // Fetch all completed calls to the specified number
        $completedCalls = $this->twilio->calls->read([
            'to' => $to,
            'status' => 'completed'
        ]);

        foreach ($completedCalls as $call) {
            // Extract call details
            $callSid = $call->sid;
            $from = $call->from;
            $to = $call->to;
            $dateTime = $call->dateCreated->format('Y-m-d H:i:s');
            $duration = $call->duration ?? '0 seconds';  // Default to '0 seconds' if duration is not available
            $direction = $call->direction;
            $status = $call->status;

            // Fetch call cost (make sure you have the right method to fetch call cost)
            $callDetails = $this->twilio->calls($callSid)->fetch();
            $price = $callDetails->price ?? '0.00';  // Default to 'N/A' if price is not available

            // Save call details to the database
            $callRecord = new Call();
            $callRecord->sid = $callSid;
            $callRecord->from = $from;
            $callRecord->to = $to;
            $callRecord->user_id = 90; // You might want to set this according to your logic
            $callRecord->contact_id = null; // You might want to set this according to your logic
            $callRecord->date_time = $dateTime;
            $callRecord->duration = $duration;
            $callRecord->direction = $direction;
            $callRecord->status = $status;
            $callRecord->price = $price;
            $callRecord->conference_id = 1;
            $callRecord->save();
        }

        \Log::info("Call details saved successfully.");
    }



    public function fetchCallDetails($n) 
    {         
                        
        $numberResponse = $this->twilio->pricing->v2->voice->numbers($n)->fetch();

        $price = $this->getCallPrice(print_r($numberResponse, true));
        
        \Log::info("Here is price fetch details =>". $price);
        
        return $price;
    }

    function getCallPrice($response) {
        // Decode the JSON response to an associative array
        $responseArray = json_decode($response, true);
        \Log::info("here is responseArray". $responseArray);
        // Check if outbound call prices are available
        if (isset($responseArray['outbound_call_prices']) && !empty($responseArray['outbound_call_prices'])) {
            // Get the first outbound call price (assuming there's at least one)
            $outboundPrice = $responseArray['outbound_call_prices'][0]['current_price'];
            
            // Return the current price
            return $outboundPrice;
        } else {
            // Handle the case where no outbound call prices are available
            return null;
        }
    }
    

    public function placeOnHold(Request $request)
    {        

        $to = $request->input('to');
        $from = $request->input('From');        
        $dialCall = $this->twilio->calls->read([
            'to' => $to,
            'status' => 'in-progress'
        ]);                
        
        if (!empty($dialCall)) {            
            foreach ($dialCall as $call) {
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


    public function transferCall(Request $request)
    {
        $dialerCallSid = $request->input('callSid');
        $to = $request->input('to');    
        $from = $request->input('From');
        $forwardNumber = $request->input('forwardNumber');
        
        $dialCall = $this->twilio->calls->read([
            'to' => $to,
            'status' => 'in-progress'
        ]);                

        if (!empty($dialCall)) {
            foreach ($dialCall as $call) {                
                $callSid = $call->sid; 
                $this->twilio->calls($callSid)->update([
                    'url' => route('hold-url'),                    
                    'method' => 'POST',                    
                ]);
            }
        } else {
            return response()->json([
                'message' => "No in-progress calls found for the number $to."
            ], 404);
        }        
        
        $this->twilio->calls($dialerCallSid)
            ->update([
                'url' => route('forward-ringing', ['forwardNumber' => $forwardNumber]),                 
                'method' => 'POST'
            ]);

        $forwardCall = $this->twilio->calls->create(
            $forwardNumber, 
            $from,        
            [
                'url' => route('waiting-room'),                
                'method' => 'POST',                    
            ]
        );

        \Log::info("here is forwarded call =>". $forwardCall);

        return response()->json(['message' => 'Call Forwarded Successfully!']);
    }


    public function waitingRoom(Request $request)
    {
        $response = new VoiceResponse();
        $dial = $response->dial();
        $conference = $dial->conference('SupportConferenceRoom', [
            'beep' => false,                             
        ]);        

        return response($response)->header('Content-Type', 'text/xml');
    }


    public function forwardRinging(Request $request)
    {
        $forwardNumber = $request->input('forwardNumber');
        $response = new VoiceResponse();                    
        $dial->number($forwardNumber);         
        return response($response, 200)->header('Content-Type', 'text/xml');
    }


    public function connectTransferCall(Request $request)
    {
        $forwardNumber = $request->input('forwardNumber');
        $dialerCallSid = $request->input('callSid');
        $childCallSid  = $request->input('childCallSid');                

        $dialCall = $this->twilio->calls->read([
            'to' => $forwardNumber,
            'status' => 'in-progress'
        ]);                

        if (!empty($dialCall)) {
            foreach ($dialCall as $call) {                
                $callSid = $call->sid; 
                $this->twilio->calls($callSid)->update([
                    'url' => route('forward-ringing'),                    
                    'method' => 'POST',                    
                ]);
            }
        } else {
            return response()->json([
                'message' => "No in-progress calls found for the number $to."
            ], 404);
        }        


        $this->twilio->calls($dialerCallSid)
        ->update([
            'url' => route('forward-ringing'),            
            'method' => 'POST'
        ]);

        $this->twilio->calls($childCallSid)
        ->update([
            'url' => route('forward-ringing'),            
            'method' => 'POST'
        ]);
    }
    

    public function transferCallConference(Request $request)
    {
        $response = new VoiceResponse();
        $dial = $response->dial();
        $conference = $dial->conference('TransferCallConferenceRoom'.time(), [
            'beep' => false,                             
            'endConferenceOnExit' => true
        ]);        

        return response($response)->header('Content-Type', 'text/xml');
    }



}

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
use Auth;
use App\Repositories\CallRepository;


class CallController extends Controller
{
    protected $twilio, $callRepository;

    public function __construct(CallRepository $callRepository)
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $this->twilio = new Client($sid, $token);       
        $this->callRepository = $callRepository; 
    }

    
    
    public function makeCall(Request $request){
        try {                             
            
            $to = $request->input('to');    
            $from = $request->input('From');                                                                                            

            $response = new VoiceResponse();   

            $dial = $response->dial('', [
                'callerId' => $request->From,
                'record'   => true
            ]);

            $to = $request->To;
            $number = Str::replaceFirst('+', '', $to);
            $callResponse = $dial->number($number);            
            $response->enqueue('supportRoom');
            $data = $request->all();                         
            $this->callRepository->saveCall($data);
            return response($response)->header('Content-Type', 'text/xml');            
            
        } catch (\Exception $e) {   
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        } 
    }
        

    public function mobileCallLeg(Request $request) 
    {   
         // Access the incoming request data
         $callSid = $request->input('callSid');
         $to = $request->input('to');
         $from = $request->input('From');
 
         $mobileCallSid = null;

        $dialCall = $this->twilio->calls->read([
            'to' => $to,
            'status' => 'ringing'
        ]);                
        

        if (!empty($dialCall)) {            
            foreach ($dialCall as $call) {
                $mobileCallSid = $call->sid; 
                $updateChildCallSid = $this->callRepository->updateMobileCallSid($callSid, $mobileCallSid);                            
                return $mobileCallSid;
            }

        } else {
            return null;
        }


         // You can now use these variables as needed
         // For example, return a response
         return response()->json([
             'callSid' => $callSid,
             'to' => $to,
             'from' => $from,
             'mobileCallSid' => $mobileCallSid,
             'message' => 'Data received successfully!',
         ]);
                
    }

    public function getCallInfo(Request $request) 
    {   
        \Log::info("Here is the data =>". print_r($request->all(), true));                
    }


    public function callDisconnected(Request $request)
    {
        try {
            $data = $request->all();      
            // \Log::info("here is the call disconnect =>". print_r($data, true));                      
            $response = $this->callRepository->updateCall($data);            
        } catch (\Exception $e) {
            \Log::info("here is the error =>". $e->getMessage());
        }        
    }


    public function checkCallStatus(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'childCallSid' => 'required|string',
        ]);

        $childCallSid = $request->input('childCallSid');

        // Initialize the Twilio client
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);

        try {
            // Fetch the call details using the childCallSid
            $callDetails = $twilio->calls($childCallSid)->fetch();

            // Prepare the response data
            $response = [
                'status' => $callDetails->status, // Call status (e.g., queued, in-progress, completed, busy, failed)
                'duration' => $callDetails->duration, // Duration of the call in seconds
                'price' => $callDetails->price, // Price of the call, if available
                'to' => $callDetails->to, // The number the call was made to
                'from' => $callDetails->from, // The number the call was made from
                'dateCreated' => $callDetails->dateCreated->format('Y-m-d H:i:s'), // Call creation date
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error fetching call status: ' . $e->getMessage()
            ], 500);
        }
    }



    public function fetchCallDetails($n) 
    {                                 
        \Log::info("here is the call disconnected function");
        $numberResponse = $this->twilio->pricing->v2->voice->numbers($n)->fetch();
        $price = $this->getCallPrice(print_r($numberResponse, true));                        
        return $price;
    }

    function getCallPrice($response) {        
        $responseArray = json_decode($response, true);        
        if (isset($responseArray['outbound_call_prices']) && !empty($responseArray['outbound_call_prices'])) {            
            $outboundPrice = $responseArray['outbound_call_prices'][0]['current_price'];            
            return $outboundPrice;
        } else {
            return null;
        }
    }
    

    public function placeOnHold(Request $request)
    {        

        $to = $request->input('to');
        $from = $request->input('From');        
        $baseUrl = route('hold-url');
        $userCallSid = $this->userCallSid($to);
        $this->updateCallUrl($userCallSid, $baseUrl);        
        return response()->json([
            'childCallSid' => $userCallSid
        ]);                                        
                   
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
        $baseUrl = route('continue-conversation');
        $this->updateCallUrl($userCallSid, $baseUrl);    
        $this->updateCallUrl($dialerCallSid, $baseUrl);        
        
        return response()->json([
            'message'  => 'Call resumed successfully',
        ]);

    }


    public function continueConversation(Request $request)
    {        
        $response = new VoiceResponse();
        $dial = $response->dial();
        $conference = $dial->conference('SupportConferenceRoom', [
            'beep' => false,                 
            'endConferenceOnExit' => true
        ]);        

        return response($response)->header('Content-Type', 'text/xml');

    }


    public function transferCall(Request $request)
    {
        $dialerCallSid = $request->input('callSid');
        $to = $request->input('to');    
        $from = $request->input('From');
        $forwardNumber = $request->input('forwardNumber');        
        $holdUrl = route('continue-conversation');
        $holdCallSid = $this->userCallSid($to);
        $this->updateCallUrl($holdCallSid, $holdUrl);                    
        
        $queryParams = [
            'forwardNumber' => $forwardNumber,
            'from' => $from
        ];
        
        $forwardUrl = route('forward-ringing', $queryParams);
        $this->updateCallUrl($dialerCallSid, $forwardUrl);            
        return response()->json(['message' => 'Call Forwarded Successfully!']);
    }



    public function forwardRinging(Request $request)
    {
        $forwardNumber = $request->input('forwardNumber');
        $from  = $request->input('from');
        \Log::info("here is the  forward number => ". $forwardNumber);
        \Log::info("here is the from number => ". $from);

        $response = new VoiceResponse();                    
        $dial = $response->dial('', ['callerId' => $from]);        
        $number = Str::replaceFirst('+', '', $forwardNumber);
        $dial->number($number);                           
        $response->enqueue('supportRoom');
        return response($response, 200)->header('Content-Type', 'text/xml');
    }


    public function connectTransferCall(Request $request)
    {
        $forwardNumber = $request->input('forwardNumber');
        $dialerCallSid = $request->input('callSid');
        $currentUser  = $request->input('to');                

        $forwardCallSid = $this->userCallSid($forwardNumber);
        $currentUserCallSid = $this->userCallSid($currentUser);

        // Define the base URL for call updates        
        $baseUrl = route('transfer-call-conference');        
        $this->updateCallUrl($forwardCallSid, $baseUrl);
        $this->updateCallUrl($currentUserCallSid, $baseUrl);
        $this->updateCallUrl($dialerCallSid, $baseUrl);
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

    public function userCallSid($number)
    {
        $dialCall = $this->twilio->calls->read([
            'to' => $number,
            'status' => 'in-progress'
        ]);                
        
        if (!empty($dialCall)) {            
            foreach ($dialCall as $call) {
                $callSid = $call->sid; 
                return $callSid;
            }

        } else {
            return null;
        }
    }


    public function updateCallUrl($callSid, $url)
    {        
        $this->twilio->calls($callSid)->update([
            'url' => $url,
            'method' => 'POST'
        ]);
    }


}

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
            $dial = $response->dial('', ['callerId' => $request->From]);
            $to = $request->To;
            $number = Str::replaceFirst('+', '', $to);
            $callResponse = $dial->number($number);            
            $response->enqueue('supportRoom');
            $data = $request->all();             
            $this->createCallRecord($data);    

            return response($response)->header('Content-Type', 'text/xml');            
            
        } catch (\Exception $e) {   
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        } 
    }
        


    public function createCallRecord($data) // Ensure the parameter is an array
    {
        try {

            \Log::info("inside the create call Record ". (print_r($data, true)));            
            // \Log::info("here is the type of data => ". gettype(print_r($data, true)));
            // \Log::info("inside the create call Record ". print_r($data, true));            
    
            // Call the repository method to save the data
            $this->callRepository->saveCall($data);
    
        } catch (\Exception $e) {
            \Log::info("here is call info =>". $e->getMessage());
        }        
    }
    


    public function getCallInfo(Request $request) 
    {                               
         // Log the incoming request to ensure it's hitting this endpoint
        Log::info('Call Status Callback', $request->all());
        // return response('OK', 200);
    }


    public function callDisconnected(Request $request)
    {
        $dialerCallSid  = $request->input('callSid');
        $childCallSid  = $request->input('childCallSid');
        $forwardNumber  = $request->input('ForwardNumber');
        $phoneNumbers  = $request->input('PhoneNumbers');


        // \Log::info("Here is the dialer call sid => ". $dialerCallSid);
        // \Log::info("Here is the child call sid => ". $childCallSid);
        // \Log::info("Here is the forward number => ". $forwardNumber);
        // \Log::info("Here is the phone numbers => ". $phoneNumbers);

        // if($dialerCallSid){
        //     $dialerCallSidResponse = $this->twilio->calls($dialerCallSid)->fetch();
        //     $dialerCallPrice = $this->getCallPrice($dialerCallSidResponse);
        //     \Log::info("dialer Call Price =>". $dialerCallPrice);
        // }
        

        // if($childCallSid){
        //     $childCallSidResponse = $this->twilio->calls($childCallSid)->fetch();
        //     $childCallPrice = $this->getCallPrice($childCallSidResponse);
        //     \Log::info("child Call Price =>". $childCallPrice);
        // }        


        // if($forwardNumber){
        //     $forwardCallSidResponse =  $this->userCallSid($forwardNumber); 
        //     $forwardCallPrice = $this->getCallPrice($forwardCallSidResponse);
        //     \Log::info("forward call Price =>". $forwardCallPrice);
        // }
        

        // $conferenceCallPrice = 0;

        // foreach ($phoneNumbers as $key => $number) {
        //     $cconferenceCallSid = $this->userCallSid($number); 
        //     $conferenceCallPrice += $this->getCallPrice($childCallSidResponse);
        //     # code...
        // }
        
                
        // \Log::info("conference call Price =>". $conferenceCallPrice);

        // $to = $request->input('to');    
                  
        // $completedCalls = $this->twilio->calls->read([
        //     'to' => $to, 
        //     'status' => 'completed',
        //     'limit' => 50
        // ]);

        // $callRecords = [];
        // \Log::info("here is the call disconnected function");
        // foreach ($completedCalls as $call) {
        //     $callSid = $call->sid;
        //     $from = $call->from;
        //     $to = $call->to;
        //     $dateTime = $call->dateCreated->format('Y-m-d H:i:s');
        //     $duration = $call->duration ?? '0 seconds';
        //     $direction = $call->direction;
        //     $status = $call->status;

        //     $callDetails = $this->twilio->calls($callSid)->fetch();
        //     $price = $callDetails->price ?? '0.00';

        //     $callRecords[] = [
        //         'sid' => $callSid,
        //         'from' => $from,
        //         'to' => $to,
        //         'user_id' => Auth::user()->id,
        //         'contact_id' => null,
        //         'date_time' => $dateTime,
        //         'duration' => $duration,
        //         'direction' => $direction,
        //         'status' => $status,
        //         'price' => $price
        //     ];
        // }

        // // Bulk insert call records
        // if (!empty($callRecords)) {
        //     Call::insert($callRecords);
        // }

        // return response()->json([
        //     'message' => 'Call details saved successfully.' 
        // ]);                     
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


    public function updateCallUrl($callSid = null, $url)
    {        
        $this->twilio->calls($callSid)->update([
            'url' => $url,
            'method' => 'POST'
        ]);
    }


}

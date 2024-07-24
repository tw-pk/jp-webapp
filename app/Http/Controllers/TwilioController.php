<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Jwt\ClientToken;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Support\Facades\Broadcast;
use App\Events\IncomingCallEvent;
use App\Models\UserNumber;
use App\Models\PhoneSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\Call;
use App\Models\Contact;
use App\Models\UserCredit;
use App\Models\CreditProduct;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateCallDetails;

class TwilioController extends Controller
{
    protected Client $twilio;

    /**
     * @throws ConfigurationException
     */
    public function __construct()
    {
        // Your Account SID and Auth Token from twilio.com/console
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');

        /** Initialize the Twilio client so it can be used */
        $this->twilio = new Client($sid, $token);
    }

    public function retrieveToken(){
        $capability = new ClientToken(config('app.TWILIO_CLIENT_ID'), config('app.TWILIO_AUTH_TOKEN'));
        $capability->allowClientOutgoing(config('app.TWILIO_VOICE_APP_SID'));
        $client_name = Auth::user()->firstname ? Auth::user()->firstname :'Agent';
        $capability->allowClientIncoming($client_name);
        $token = $capability->generateToken();

        return response()->json($token);
    }


    public function checkBalance($userId)
    {
        $id = json_decode($userId);
        Log::info("here is user id =>". $userId);
    

        $userId = $id;
        $user = User::with('invitationsMember')->where('id', $userId)->first();


        $teamleadId;
        if ($user->hasRole('Admin')) {
            Log::info('Inside Admin role => ' . $userId);
            $teamleadId = $userId;
        } else {            
            $invitationMember = $user->invitationsMember;
            Log::info('Here is Invitation member relation => ' . $invitationMember);
            Log::info('Here is Invitation member relation of user (admin/teamlead user id) => ' . $invitationMember->user_id);
            $teamleadId = $invitationMember->user_id;
        }

        $creditInformation = UserCredit::where('user_id', $teamleadId)->first();
    
        // Check if $creditInformation is null
        if (!$creditInformation) {
            return response()->json([
                'error' => 'User credit information not found.'
            ], 404);
        }
    
        $thresholdValue = CreditProduct::where('price_id', $creditInformation->threshold_value)->pluck('price')->first();
    
        // Check if $thresholdValue is null
        if (is_null($thresholdValue)) {
            return response()->json([
                'error' => 'Threshold value not found.'
            ], 404);
        }
    
        if ($creditInformation->credit < $thresholdValue) {
            return response()->json([
                'message' => 'Your balance is currently low. Please contact your team lead.',
                'lowBalance' => true
            ], 200);
        }
    
        return response()->json([
            'message' => 'Your balance is sufficient.',
            'lowBalance' => false
        ], 200);
    }
    
   public function dial(Request $request){
        try {                
            $response = new VoiceResponse();
            $dial = $response->dial('', ['callerId' => $request->From]);
            $to = $request->To;
            $number = Str::replaceFirst('+', '', $to);
            $dial->number($number);
            $data = $request->all();
            
            if ($request->CallStatus === 'completed') {
                $this->updateCallDetails($data);
            } else {
                $this->webhookCallstatus($data, $number);
            }
            
            echo $response;
        } catch (\Exception $e) {
            Log::error('Twilio API dial error: ' . $e->getMessage());
        }        
                
    }

    public function incomingCall(Request $request){

        $response = new VoiceResponse();
        Log::info('Incoming Call Response: ');
        Log::info($request->all());
        
        $UserNumber = UserNumber::where('active', true)->where('phone_number', $request->To)->first();
        if (!empty($UserNumber)) {
           
            $response->say('Hello! Kindly wait you are being connected to the call....');
            //$authenticatedUserId = Auth::check() ? Auth::id();
            // Connect the call with your Twilio Device (replace 'your-device-identity' with your actual Device identity)
            // Check if the user ID from the request matches the authenticated user's ID
            $phoneSetting = PhoneSetting::where('user_id', $UserNumber->user_id)
            ->where('phone_number', $UserNumber->phone_number)
            ->first();

            $user = User::where('id', $UserNumber->user_id)
                    ->select('firstname', 'lastname')
                    ->first();
            $client_name = $user->firstname .' '. $user->lastname;
            
            //Select mobile number
           if(!empty($phoneSetting->external_phone_number) && $phoneSetting->fwd_incoming_call == 'mobile_number'){
                
                $numberTo = Str::replaceFirst('+', '', $phoneSetting->external_phone_number);
                $dial = $response->dial('', ['callerId' => $request->input('From')]);
                if (!empty($client_name)) {
                    $dial->client($client_name);
                }
                $dial->number($numberTo);

            //Select Web & Desktop Apps    
           }else if(!empty($phoneSetting->external_phone_number) && $phoneSetting->fwd_incoming_call == 'web_desktop_apps' && $phoneSetting->unanswered_fwd_call == 'external_number'){
                
                $numberTo = Str::replaceFirst('+', '', $phoneSetting->external_phone_number);
                $dial = $response->dial('', ['callerId' => $request->input('From')]);
                if (!empty($client_name)) {
                    $dial->client($client_name);
                }
                $dial->number($numberTo);

                //Select Web & Desktop Apps and Dismiss Call
            }else if($phoneSetting->fwd_incoming_call == 'web_desktop_apps' && $phoneSetting->unanswered_fwd_call == 'dismiss_call'){
                $response->reject(['reason' => 'busy']);

                //Select Web & Desktop Apps and Voicemail
            }if($phoneSetting->fwd_incoming_call == 'web_desktop_apps' && $phoneSetting->unanswered_fwd_call == 'voicemail'){
            
                $response->say('Please leave a message after the beep.');
                $response->record([
                    'action' => route('handle-recording'),
                    'maxLength' => 120,
                    'transcribe' => true,
                    'playBeep' => true,
                    'transcribeCallback' => route('handle-transcription')
                ]);
                $response->say('Thank you for your message. Goodbye!');
                $response->hangup();
            }

            
           //dd($phoneSetting);
            // $data['broadcaster'] = $request->broadcaster;
            // $data['receiver'] = $request->receiver;
            // $data['offer'] = $request->offer;

            // event(new StreamOffer($data));
            $dial = $response->dial();
            $dial->client('browser-client-identifier');
            event(new IncomingCallEvent($request->all()));
            //return response($response)->header('Content-Type', 'text/xml');

            ////
            // $response->say('Hello! Kindly wait call is being forwarded to admin....');
            // //These static names and phonenumbers should become dynamic in the coming days when the functionalify is complete.
            // $client_name = 'Usman Ghani';
            // $number = Str::replaceFirst('+', '', '923447431371');
            
            // $dial = $response->dial('', ['callerId' => $request->input('From')]);
            // $dial->client($client_name);
            // $dial->number($number);
           
        } else {
    
            $response->say('Sorry! The number you are calling is not currently active for a user.');
        }
        // Answer the incoming call
        $response->say('Call ended....');

        // Render the TwiML response
        echo $response;
    }

    public function webhook_ten_dlc(Request $request){

        Log::info('Status callback Webhook: ');
        Log::info($request->all());
    }
    public function get_countries()
    {
        $countryList = $this->fetchCountryList();
        dd($countryList);
        // Use the country list as needed
        // ...
    }

    public function fetchCountryList()
    {
        // Fetch the list of available countries for phone numbers
        $countries = $this->twilio->voice->countries->read();
        dd($countries);
        
        // Retrieve the country ISO codes
        $countryISOs = array_map(function ($country) {
            return $country->isoCountry;
        }, $countries);
        
        // Return the country ISO codes
        return $countryISOs;
    }

    public function handleRecording(Request $request)
    {
        // Save the recording URL and other details
        $recordingUrl = $request->input('RecordingUrl');
        $recordingSid = $request->input('RecordingSid');
        $callSid = $request->input('CallSid');

        // Store recording details in the database or perform any other logic
        // For example, you can store the URL in the database

        // Optionally, you can download the recording file and save it to local storage
        $filePath = 'recordings/' . $recordingSid . '.mp3';
        Storage::put($filePath, file_get_contents($recordingUrl . '.mp3'));

        return response('Recording handled', 200);
    }

    public function handleTranscription(Request $request)
    {
        // Handle transcription if needed
        $transcriptionText = $request->input('TranscriptionText');
        $recordingSid = $request->input('RecordingSid');

        // Store transcription details in the database or perform any other logic

        return response('Transcription handled', 200);
    }


    public function webhookCallstatus($d, $number)
    {
        $data = json_encode($d, true);
        $price = $this->fetchCallDetails($number);
        $price = floatval(trim($price, '"'));
        Log::info("Here is all detail =>".$data);
        // Create or update a Call record
        $call = new Call();
        $call->sid = $d['CallSid'];
        $call->from = $d['From'];
        $call->to = $d['To'];
        $call->user_id = $d['agent'];
        $call->contact_id = null;
        $call->date_time = now();
        $call->duration = '0 seconds';
        $call->direction = $d['Direction'];
        $call->status = $d['CallStatus'];
        $call->price = $price;
        $call->save();
    }

    
    public function fetchCallDetails($n) 
    { 
        Log::info("Inside fetchCallDetails");
        
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        
        $number = $twilio->pricing->v2->voice->numbers($n)->fetch();
        
        $price = isset($number->inboundCallPrice['current_price']) 
        ? json_encode($number->inboundCallPrice['current_price']) 
        : (isset($number->outboundCallPrices[0]['current_price']) 
            ? json_encode($number->outboundCallPrices[0]['current_price']) 
            : null);
        Log::info("inside fetch details =>". $price);
        return $price;
    }


    public function fetchUserCredit($adminId, $price)
    {
        try {
            Log::info('here is Teamlead user is => '. $adminId);
            Log::info('here is call Price => '. $price);
            $adminCredit = UserCredit::where('user_id', $adminId)->first();
            Log::info('here is user credit => '.$adminCredit);
            $callPrice = $price;
            $priceWithMargin = $callPrice * 1.15;
            $adminCredit->credit -=  $priceWithMargin; 
            $adminCredit->save();
            Log::info('Deducte admin price');
        } catch (\Exception $e) {
            Log::error('Error updating User credits : ' . $e->getMessage());
        }
    }


    public function updateCallDetails($d)
    {
        DB::beginTransaction();

        try {
            $callSid = $d['CallSid'];
            $client = new Client(config('app.TWILIO_CLIENT_ID'), config('app.TWILIO_AUTH_TOKEN'));
            $call = $client->calls($callSid)->fetch();

            Log::info('Present call SID info => ' . print_r($call->toArray(), true));

            $startTime = $call->startTime ? $call->startTime->format('Y-m-d H:i:s') : null;
            $endTime = $call->endTime ? $call->endTime->format('Y-m-d H:i:s') : null;
            $duration = $call->duration ? (int) $call->duration : 0; // Duration in seconds
            $status = $call->status;
            $price = $call->price; // This might be null if not provided
            $dateTime = $startTime . '-' . $endTime;

            // Prepare data for updating
            $updateData = [
                'duration' => $duration . " seconds",
                'status' => $status,
                'date_time' => $dateTime,
            ];

            if ($price !== null) {
                // Update price if provided
                $updateData['price'] = abs($price);
            } else {
                // Fetch existing call details from the database
                $callDetails = Call::where('sid', $callSid)->first();
                
                // If the call details exist and have a price
                if ($callDetails) {
                    $pricePerMinute = $callDetails->price; // Fetch the price per minute from existing call details
                    
                    // Convert duration to minutes
                    $durationInMinutes = $duration / 60;
                    
                    // Calculate the price
                    $calculatedPrice = $durationInMinutes * $pricePerMinute;
                    
                    // Update the price with the calculated value
                    $updateData['price'] = $calculatedPrice;
                }
            }

            // Update call details in the database
            Call::where('sid', $callSid)->update($updateData);

            // Optional: Fetch the updated call details
            $callDetails = Call::where('sid', $callSid)->first();
            Log::info('Updated Call Details:', [
                'price' => $callDetails->price,
                'duration' => $callDetails->duration
            ]);

            // Additional logic for handling user credits
            $userId = $callDetails->user_id;
            $user = User::with('invitationsMember')->where('id', $userId)->first();

            if ($user->hasRole('Admin')) {
                Log::info('Inside Admin role => ' . $userId);
                $this->fetchUserCredit($userId, abs($callDetails->price));
            } else {
                Log::info('Inside member role');
                $invitationMember = $user->invitationsMember;
                Log::info('Here is Invitation member relation => ' . $invitationMember);
                Log::info('Here is Invitation member relation of user (admin/teamlead user id) => ' . $invitationMember->user_id);
                $this->fetchUserCredit($invitationMember->user_id, abs($callDetails->price));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating call details: ' . $e->getMessage());
        }
    }

}

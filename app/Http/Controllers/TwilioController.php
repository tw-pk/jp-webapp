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
        $capability->allowClientOutgoing(env('TWILIO_VOICE_APP_SID'));
        $client_name = Auth::user()->firstname ? Auth::user()->firstname :'Agent';
        $capability->allowClientIncoming($client_name);
        $token = $capability->generateToken();

        return response()->json($token);
    }

    public function dial(Request $request){

        $response = new VoiceResponse();
        $dial = $response->dial('', ['callerId' => $request->From]);
        $to = $request->To;
        $number = Str::replaceFirst('+', '', $to);
        Log::info('TO'. $request->To);
        $dial->number($number);

        // Return the XML response
        echo $response;
    }

    public function incomingCall(Request $request){

        Log::info('Incoming Call Response: ');
        Log::info($request->all());

         //To
        $response = new VoiceResponse();
        // Answer the incoming call
        $response->say('Hello! Kindly wait you are being connected to the call....');
        // Connect the call with your Twilio Device (replace 'your-device-identity' with your actual Device identity)
        //$dial = $response->dial();
       
        //These static names and phonenumbers should become dynamic in the coming days when the functionalify is complete.
        $client_name = 'Usman Ghani';
        $number = Str::replaceFirst('+', '', '923447431371');
        
        $dial = $response->dial('', ['callerId' => $request->input('From')]);
        $dial->client($client_name);
        $dial->number($number);

        // Answer the incoming call
        $response->say('Thanks! Call ended....');

        // Render the TwiML response
        echo $response;
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



}

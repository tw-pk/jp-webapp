<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CallController extends Controller
{

    public function handleDialStatus(Request $request)
    {
        $response = new VoiceResponse();

        if ($request->DialCallStatus === 'completed') {
            $response->say("Transfer successful. Thank you.");
        } else {
            $response->say("The transfer was not successful.");
        }

        return response($response)->header('Content-Type', 'text/xml');
    }

    public function holdCall(Request $request)
    {
        $callSid = $request->input('CallSid');

        // Use Twilio's API to update the call with hold music or a message
        $response = new VoiceResponse();
        $response->say("Please hold while we connect you."); // You can also use play() for hold music
        $response->pause(['length' => 300]); // Keeps the call paused for 5 minutes

        return response($response)->header('Content-Type', 'text/xml');
    }

    public function resumeCall(Request $request)
    {
        $callSid = $request->input('CallSid');

        // Use Twilio's API to resume the call
        $response = new VoiceResponse();
        $response->say("Resuming your call now.");

        // Redirect the call to continue the conversation
        $response->dial()->number('+1234567890'); // Agent's number

        return response($response)->header('Content-Type', 'text/xml');
    }
}

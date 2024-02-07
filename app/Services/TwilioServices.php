<?php

namespace App\Services;

use Twilio\Rest\Client;


class TwilioService
{
    protected $twilio;

    public function __construct()
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $this->twilio = new Client($sid, $token);
    }

    public function makeTwilioCall($callTypeTab)
    {
        try {
            $calls = $this->twilio->calls->read($callTypeTab, 100);
            // Handle the Twilio API response as needed
            return $calls;
        } catch (\Exception $e) {
            // Handle exceptions and errors
            return null;
        }
    }
}

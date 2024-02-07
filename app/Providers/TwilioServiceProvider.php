<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client;

class TwilioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('Twilio\Rest\Client', function ($app) {
            return new Client(
                config('services.twilio.TWILIO_CLIENT_ID'),
                config('services.twilio.TWILIO_AUTH_TOKEN')
            );
        });
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twilio-client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function handle()
    {
        // Phone number to lookup
        $phoneNumber = '+12179925655'; // Replace with the phone number you want to lookup
        $subscriptionId = 'sub_1NQuO4EUB7mVnT1OdVo4aAcg';
        $stripe = new \Stripe\StripeClient(config('app.STRIPE_SECRET'));

        $subscription = $stripe->subscriptions->retrieve(
            $subscriptionId,
            []
        );

        //retrieve payment method details
        $payment_method = $stripe->paymentMethods->retrieve(
            $subscription->default_payment_method,
            []
        );

        dd($payment_method->card);

    }
}

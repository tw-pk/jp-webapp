<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StripeSubscriptionService;
use Illuminate\Console\Command;

class CreateStripeCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-stripe-customer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stripe_subscription_service = new StripeSubscriptionService();
        $stripe_subscription_service->createSubscription(User::find(1)->first());
    }
}

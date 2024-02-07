<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\StripeWebhooks\PaymentIntentSucceededJob;

class PaymentIntentSucceeded extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:payment-intent-succeeded';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle payment intent succeeded';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Dispatch the PaymentIntentSucceededJob
        dispatch(new PaymentIntentSucceededJob());

        $this->info('Payment intent succeeded job dispatched.');
    }
}

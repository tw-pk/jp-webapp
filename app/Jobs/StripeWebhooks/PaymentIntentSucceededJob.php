<?php

namespace App\Jobs\StripeWebhooks;

use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JetBrains\PhpStorm\NoReturn;
use Stripe\Stripe;
use Stripe\StripeClient;
use Spatie\WebhookClient\Models\WebhookCall;
use Illuminate\Support\Facades\Log;

class PaymentIntentSucceededJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public WebhookCall $webhookCall;

    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Execute the job.
     */
    #[NoReturn] public function handle(): void
    {
        Log::info('This is an informational message.');

        $paymentIntent = $this->webhookCall->payload['data']['object'];
        $paymentData = $paymentIntent['charges']['data'][0];
        $customerId = $paymentData['customer'];

        $amount = $paymentData['amount'] / 100;
        $amountStatus = $paymentData['status'];

        if ($amountStatus === 'succeeded') {
            $user = User::where('stripe_id', $customerId)->first();
            if ($user) {
                if ($user->credit) {
                    $user->credit->credit += $amount;
                    $user->credit->save();
                } else {
                    UserCredit::create([
                        'credit' => $amount,
                        'user_id' => $user->id,
                    ]);
                }
            }
        }
    }
}

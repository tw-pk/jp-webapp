<?php

namespace App\Jobs\StripeWebhooks;

use App\Models\Payment;
use App\Models\Plans;
use App\Models\User;
use App\Models\UserCredit;
use App\Notifications\CreditAwarded;
use App\Notifications\NewSubscriptionCreated;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionItem;
use Spatie\WebhookClient\Models\WebhookCall;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class CheckoutSucceededJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var WebhookCall */
    public $webhookCall;

    /**
     * Create a new job instance.
     */
    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Execute the job.
     * @throws ApiErrorException
     *
     */
    public function handle(): void
    {
        //payload
        $checkout = $this->webhookCall->payload['data']['object'];
        $subscriptionId = $checkout['subscription'];

        //retrieve user
        $user = User::where('email', $checkout['customer_details']['email'])->first();


        $stripe = new \Stripe\StripeClient(config('app.STRIPE_SECRET'));
        $subscription = $stripe->subscriptions->retrieve(
            $subscriptionId,
            []
        );

        //retrieve subscription details
        $productId = $subscription->items->data[0]->price->product;
        $subscription_interval = $subscription->items->data[0]->price->recurring->interval;
        $subscription_starts_at = Carbon::createFromTimestamp($subscription->start_date);
        $subscription_trial_ends_at = Carbon::createFromTimestamp($subscription->trial_end);

        //retrieve payment method details
        $payment_method = $stripe->paymentMethods->retrieve(
            $subscription->default_payment_method,
            []
        );

        $payment_method_card_brand = $payment_method->card->brand;
        $payment_method_card_last_4 = $payment_method->card->last4;
        $payment_method_card_expiry_month = $payment_method->card->exp_month;
        $payment_method_card_expiry_year = $payment_method->card->exp_year;

        // Assuming you have the month and year stored in variables
        $month = $payment_method_card_expiry_month;
        $year = $payment_method_card_expiry_year;

        // Create a Carbon instance with the specified month and year
        $timestamp = Carbon::create($year, $month, 1, 0, 0, 0);
//
//        // Access the timestamp value
//        $timestampValue = $timestamp->timestamp;


        if ($user) {
            $subsc = Subscription::create([
                'name' => Plans::where('name', 'Basic')->first()->name,
                'trial_ends_at' => $subscription_trial_ends_at,
                'quantity' => 1,
                'stripe_price' => Plans::where('name', 'Basic')->first()->stripe_price,
                'stripe_status' => 'active',
                'stripe_id' => $user->stripe_id,
                'user_id' => $user->id
            ]);

            $user->pm_last_four = $payment_method_card_last_4;
            $user->trial_ends_at = $subscription_trial_ends_at;
            $user->pm_type = $payment_method_card_brand;
            $user->save();

            $newSubscription = SubscriptionItem::create([
                'stripe_price' => Plans::where('name', 'Basic')->first()->stripe_price,
                'stripe_product' =>  $productId,
                'subscription_id' => $subsc->id,
                'stripe_id' => $user->stripe_id,
                'quantity' => 1
            ]);

            if($newSubscription){

                $user->notify(new NewSubscriptionCreated($subsc->id));

                $credit = $this->creditCustomerAccount($user, [
                    'amount' => 5,
                    'currency' => 'usd'
                ]);

                if($credit){
                    $user->notify(new CreditAwarded($credit->credit));
                }
            }
        }
    }

    /**
     */
    private function creditCustomerAccount(User $customer, array $payload): ?UserCredit
    {
        $customer_credit = $customer->credit;
        if($customer_credit){
            $customer_credit->credit->credit += $payload['amount'];
            $customer_credit->save();

            return $customer_credit;
        }
        else{
            $customer_credit = new UserCredit();
            $customer_credit->user_id = $customer->id;
            $customer_credit->credit += $payload['amount'];
            $customer_credit->save();

            return $customer_credit;
        }
    }
}

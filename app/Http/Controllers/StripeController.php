<?php

namespace App\Http\Controllers;

use App\Models\CreditProduct;
use App\Models\Plans;
use App\Models\User;
use App\Services\StripeSubscriptionService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Plan;
use Stripe\Token;
use Stripe\Webhook;

class StripeController extends Controller
{

    /**
     * @throws ApiErrorException
     */
    public function createSession(Request $request)
    {
        $stripe_price_id = Plans::where('name', 'Basic')->first()->stripe_price;

        return Auth::user()->newSubscription('Basic', $stripe_price_id)
            ->trialUntil(Carbon::now()->addMonth())
            ->checkout();
    }

    /**
     * @throws ApiErrorException
     */
    public function createCheckoutSession(Request $request)
    {
        $stripe_price_id = Plans::where('name', 'Basic')->first()->stripe_price;

        $checkout = Auth::user()->newSubscription('Basic', $stripe_price_id)
            ->trialUntil(Carbon::now()->addMonth())
            ->checkout([
                "success_url" => config('app.url') . "/dashboards",
                "cancel_url" => config('app.url') . "/dashboards"
            ]);

        return response()->json([
            'checkout_url' => $checkout->url
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createTopUpSession(Request $request)
    {
        $request->validate([
            'topUpId' => 'required'
        ]);

        $topUp = CreditProduct::find($request->topUpId);

        if (!$topUp) {
            return response()->json(['message' => 'No such top product exists'], 404);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer' => Auth::user()->stripe_id,
            'line_items' => [
                [
                    'price' => $topUp->price_id,
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => config('app.url') . '/pages/top-up-credit?payment=success',
            'cancel_url' => config('app.url') . '/pages/top-up-credit',
            'metadata' => [
                'customer_name' => Auth::user()->fullName(),
            ]
        ]);

        return response()->json([
            'url' => $session->url
        ]);
    }

    public function topUpSuccess(Request $request)
    {
        \Log::info('top up success', $request->all());
        return response()->json($request->all());
    }

    public function createTopUpSessionIntent(Request $request)
    {
        $stripe = new StripeClient(config('app.STRIPE_SECRET'));
        $creditProduct = CreditProduct::find($request->creditProductId);
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $creditProduct->price,
            'currency' => 'usd'
        ]);

        return response()->json([
            'id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentIntent()
    {
        $stripe = new StripeClient(config('app.STRIPE_SECRET'));
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => 100,
            'currency' => 'usd'
        ]);
        return response()->json([
            'id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    public function payment(Request $request)
    {
        // Set your Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Create a customer in Stripe
            $customer = Customer::create([
                'email' => $request->email,
                'source' => $request->stripeToken, // Stripe token obtained from the client-side using Stripe.js or Elements
            ]);

            // Create a plan in Stripe
            $plan = Plan::create([
                'product' => [
                    'name' => 'Your Product Name', // Replace with your product name
                ],
                'nickname' => 'Monthly Plan',
                'interval' => 'month',
                'currency' => 'usd',
                'amount' => 1000, // Replace with the actual amount in cents for your monthly subscription
                'trial_period_days' => 30,
            ]);

            // Subscribe the customer to the plan with trial
            $subscription = Subscription::create([
                'customer' => $customer->id,
                'items' => [
                    [
                        'price' => $plan->id,
                    ],
                ],
            ]);

            // Store the Stripe customer ID, subscription ID, and plan ID in your database
            $user = User::create([
                //'name' => $request->name,
                //'email' => $request->email,
                'stripe_customer_id' => $customer->id,
                'stripe_subscription_id' => $subscription->id,
                'stripe_plan_id' => $plan->id,
            ]);

            // Redirect or perform any other necessary actions
        } catch (Exception $e) {
            // Handle any errors that occur during the subscription creation process
        }
    }

    public function checkUserSubscription()
    {
        if (!is_null(Auth::user()->stripe_id)) {
            if (Auth::user()->subscription('Basic')) {
                //            Auth::user()->newSubscription('Basic', Plans::where('name', '=', 'Basic')->first()->stripe_price)->add();
                if (Auth::user()->subscription('Basic')->onTrial() || Auth::user()->subscribedToPrice(Plans::where('name', '=', 'Basic')->first()->stripe_price)) {
                    return response()->json([
                        'status' => true,
                        'message' => 'User is subscribed to Basic Plan'
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Subscription has ended'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User is not subscribed to any subscription'
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'User is not subscribed to any subscription'
            ]);
        }
    }

    public function getPlanDetails()
    {
        if (Auth::user()->subscribed('Basic')) {
            $subscription = [
                'name' => Auth::user()->subscription('Basic')->name,
                'onTrial' => (bool)Auth::user()->subscription('Basic')->trial_ends_at,
                'onGracePeriod' => Auth::user()->subscription('Basic')->onGracePeriod() ?? false,
                'expiry' => Auth::user()->subscription('Basic')->trial_ends_at ? Carbon::parse(Auth::user()->subscription('Basic')->trial_ends_at)->format('M d, Y') : Carbon::parse(Auth::user()->subscription('Basic')->ends_at)->format('M d, Y'),
                'price' => "$" . number_format(Plans::where('name', 'Basic')->first()->price, 2),
                'totalDays' => Carbon::now()->daysInMonth,
                'daysSpent' => Auth::user()->subscription('Basic')->trial_ends_at ? Carbon::now()->daysInMonth - Carbon::parse(Auth::user()->subscription('Basic')->trial_ends_at)->diffInDays(Carbon::now()->format('Y-m-d')) : Carbon::now()->daysInMonth - Carbon::parse(Auth::user()->subscription('Basic')->ends_at)->diffInDays(Carbon::now()->format('Y-m-d')),
                'credit' => number_format(abs(Auth::user()->rawBalance()) / 100, 2),
                'cardName' => Auth::user()->paymentMethods()->first()?->billing_details?->name,
                'cardExpiryDate' => Auth::user()->paymentMethods()->first()?->card?->exp_month . "/" . Auth::user()->paymentMethods()->first()?->card?->exp_year,
                'cardLastFour' => Auth::user()->paymentMethods()->first()?->card?->last4,
                'cardBrand' => Auth::user()->paymentMethods()->first()?->card?->brand
            ];
            return response()->json([
                'status' => true,
                'subscription' => $subscription
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'You are not subscribed to any plan'
            ]);
        }
    }

    public function invoices(Request $request)
    {
        $invoices = [];
        $stripeInvoices = Auth::user()->invoices();
        foreach ($stripeInvoices as $stripeInvoice) {
            $invoices[] = [
                'id' => $stripeInvoice->number,
                'status' => $stripeInvoice->status,
                'total' => $stripeInvoice->total,
                'balance' => $stripeInvoice->ending_balance,
                'invoice_url' => $stripeInvoice->hosted_invoice_url,
                'date' => Carbon::createFromTimeString($stripeInvoice->effective_at)->format('d M, Y'),
                'createdAt' => Carbon::createFromTimeString($stripeInvoice->created)->format('d M, Y')
            ];
        }

        return response()->json([
            'status' => true,
            'invoices' => $invoices,
            'totalInvoices' => Auth::user()->invoices()->count(),
            'page' => 1
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createPaymentMethod(Request $request)
    {
        $request->validate([
            'pmId' => 'required',
        ]);

        $result = Auth::user()->addPaymentMethod($request->pmId);
        if ($result->id) {
            $payment_method = new \App\Models\PaymentMethod();
            $payment_method->user_id = Auth::user()->id;
            $payment_method->pmId = $result->id;
            $payment_method->card_name = $result->card->name;
            $payment_method->card_email = $result->card->email;
            $payment_method->expiry_year = $result->card->exp_year;
            $payment_method->expiry_month = $result->card->exp_month;
            $payment_method->save();

            return response()->json([
                'status' => true,
                'message' => 'A new payment has been added.'
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong!'
            ]);
        }
    }

    public function defaultPaymentMethod()
    {
        return response()->json([
            'hasDefaultPaymentMethod' => Auth::user()->defaultPaymentMethod() != null,
        ]);
    }

    public function paymentMethods()
    {
        $paymentMethods = [];
        foreach (Auth::user()->paymentMethods() as $paymentMethod) {
            $paymentMethods[] = [
                'id' => encrypt($paymentMethod->id),
                'cardLastFour' => $paymentMethod->card->last4,
                'cardName' => $paymentMethod->billing_details->name,
                'cardEmail' => $paymentMethod->billing_details->email,
                'brand' => $paymentMethod->card->brand,
                'cardExpiryDate' => $paymentMethod->card->exp_month . "/" . $paymentMethod->card->exp_year,
                'isDefault' => Auth::user()->defaultPaymentMethod()?->pmId === $paymentMethod->id
            ];
        }
        return response($paymentMethods);
    }
}

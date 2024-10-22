<?php

namespace App\Console\Commands;

use http\Client\Curl\User;
use Illuminate\Console\Command;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class UpdateCustomerBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-customer-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws ApiErrorException
     * @throws IncompletePayment
     */
    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = \App\Models\User::find(47);
        $amount = $this->ask('enter amount') * 100;
        $inv = $user->invoiceFor('Credit', $amount);
        $invoice = Invoice::retrieve($inv->asStripeInvoice()->id);
        
        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            'customer' => $invoice->customer,
            'payment_method' => 'pm_1NrfNoEUB7mVnT1Ojz9zpnJT',
            'confirm' => true
        ]);

        // Check the payment intent status
        if ($paymentIntent->status === 'succeeded') {
            // Payment was successful
            dd($paymentIntent->status);
        } else {
            dd($paymentIntent->status);
        }



//        // Create a negative invoice item to offset the charge
//        $invoice = InvoiceItem::create([
//            'customer' => $user->stripe_id, // Stripe customer ID
//            'amount' => $amount, // Convert to cents
//            'currency' => 'usd', // Replace with your desired currency
//            'description' => 'Credit applied',
//        ]);
//
//
//        $customer = Customer::update($user->stripe_id, [
//            'balance' => $amount
//        ]);

    }
}

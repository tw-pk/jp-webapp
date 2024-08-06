<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use App\Models\User;
use App\Models\CreditProduct;
use Illuminate\Support\Facades\Log;

class AutoTopUpPaymentService
{
    private $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }


    public function checkAndTopUp(User $user)
    {
        // Fetch the recharge value (price ID) from the CreditProduct model
        $rechargePriceId = CreditProduct::where('price_id', $user->credit->recharge_value)
        ->pluck('price_id')
        ->first();

        if($rechargePriceId){
            // Create an invoice item for the user based on their recharge value
            $this->createInvoiceItem($user, $rechargePriceId);
            // Create and pay the invoice
            $invoice = $this->createInvoice($user);
            $invoice = $this->finalizeInvoice($invoice);
            $invoice = $this->payInvoice($invoice);

            // Handle the payment result
            // if ($invoice->status == 'paid') {
            //     // Update user's balance or any other necessary fields
            //     $user->credit->credit += $invoice->amount_paid / 100; // Assuming the amount is in cents
            //     $user->credit->save();
            // } else {
            //     // Handle payment failure
            //     // You might want to log this or notify the user
            // }
        }
    }

    protected function createInvoiceItem(User $user, $priceId)
    {
        // Create the invoice item using the price ID
        InvoiceItem::create([
            'customer' => $user->stripe_id, // Use stripe_id from the user table
            'price' => $priceId,
            'currency' => 'usd',
            'description' => 'Automatic top-up based on subscription plan',
        ]);
    }

    protected function createInvoice(User $user)
    {
        return Invoice::create([
            'customer' => $user->stripe_id, // Use stripe_id from the user table
            //'auto_advance' => true, // Automatically finalize and attempt payment
            'auto_advance' => false,
        ]);
    }

    protected function finalizeInvoice(\Stripe\Invoice $invoice)
    {
        return $invoice->finalizeInvoice();
    }

    protected function payInvoice(\Stripe\Invoice $invoice)
    {
        return $invoice->pay();
    }


}

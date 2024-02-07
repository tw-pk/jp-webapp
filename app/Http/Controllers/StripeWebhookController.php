<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Log;

class StripeWebhookController extends WebhookController
{
    /**
     * Handle the incoming webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        return parent::handleWebhook($request);
    }
}

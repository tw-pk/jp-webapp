<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'stripe/*',
        'api/twilio/dial',
        'twilio/dial',
        'voice/*',
        'https://checkout.stripe.com/*',
        'api/twilio-sms'
    ];
}

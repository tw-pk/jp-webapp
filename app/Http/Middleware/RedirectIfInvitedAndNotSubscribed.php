<?php

namespace App\Http\Middleware;

use App\Models\Plans;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInvitedAndNotSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
        }

        if($request->user()->invitations->count() && (!$request->user()->subscribed('Basic') || !$request->user()->onTrial('Basic'))){
            Stripe::setApiKey(config('services.stripe.secret'));

            $stripe_price_id = Plans::where('name', 'Basic')->first()->stripe_price;

            if(!Auth::user()->stripe_id){
                Auth::user()->createAsStripeCustomer();
            }

            $checkout = Session::create([
                'customer' => Auth::user()->stripe_id,
                'success_url' => config('app.url')."/dashboards",
                "cancel_url" => config('app.url').'/team-members/invite',
                "line_items" => [
                    [
                        'price' => $stripe_price_id,
                        'quantity' => Auth::user()->numbers->count()
                    ],
                ],
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'subscription_data' => [
                    'trial_period_days' => 30,
                ],
                'phone_number_collection' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'customer_name' => Auth::user()->fullName(),
                ]
            ]);

            return redirect()->to($checkout->url)
                ->header('Access-Control-Allow-Origin', '*');
        }
        return $next($request)
            ->header('Access-Control-Allow-Origin', '*');
    }
}

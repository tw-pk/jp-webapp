<?php

namespace App\Services;

use App\Models\Plans;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Cashier\Exceptions\CustomerAlreadyCreated;
use Stripe\StripeClient;

class StripeSubscriptionService
{
    private $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient();
    }


    /**
     * @return array
     */
    private function checkOrCreateStripeCustomer():array
    {
        if (!is_null($this->user->stripe_id)) {
            return [
                'status' => true,
                'message' => 'No action required, customer already exists'
            ];
        } else {
            try {
                $stripe_customer = $this->user->createAsStripeCustomer([
                    'name' => $this->user->firstname." ".$this->user->lastname
                ]);
                return [
                    'status' => true,
                    'message' => 'A new stripe id associated with the the customer provided'
                ];
            } catch (CustomerAlreadyCreated $e) {
                return [
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
    }

    /**
     * @param User|null $user
     * @return JsonResponse
     */
    public function createSubscription(User $user=null): JsonResponse
    {
        $this->user = $user;
        $stripe_customer = $this->checkOrCreateStripeCustomer();

        if(!$stripe_customer['status']){
            return response()->json($stripe_customer);
        }

//        $this->user->newSubscription();



        return response()->json($stripe_customer);
    }


}

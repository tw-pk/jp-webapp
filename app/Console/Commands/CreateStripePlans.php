<?php

namespace App\Console\Commands;

use App\Models\Plans;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;

class CreateStripePlans extends Command
{
    private $stripe;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CreateStripePlans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws ApiErrorException
     */
    public function handle()
    {
        //create stripe plans
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $this->createPlan($this->stripe);
    }

    /**
     * @throws ApiErrorException
     */
    private function createPlan($stripe)
    {

        // Asking user to enter the name of the plan and product he wants to create on stripe
        $product_name = "Basic";
        $amount = 200;

//        //Retrieve stripe product
//        $stripe_product = $this->createProduct($stripe, $product_name);
//
//        //create stripe plan associated to the product
//        $stripe_plan = $stripe->prices->create([
//            'unit_amount' => $amount,
//            'currency' => 'usd',
//            'recurring' => ['interval' => 'month'],
//            'product' => $stripe_product->id,
//            config('stripe.'.$product_name)
//        ]);

        $stripe_price = \Stripe\Price::all(['product' => 'prod_Of53fE4drl7bUc'])->first();

        if(!Plans::where('stripe_price', $stripe_price->id)->first()){
            Plans::create([
                'name' => $product_name,
                'slug' => implode('_', [$product_name, 'month']),
                'price' => $amount / 100,
                'interval' => $stripe_price->recurring->interval,
                "stripe_price" => $stripe_price->id
            ]);
        }else{
            $this->info('Already exists');
        }
    }

    private function createProduct($stripe, $type)
    {
        return $stripe->products->create([
            'name' => $type
        ]);
    }
}

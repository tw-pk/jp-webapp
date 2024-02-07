<?php

namespace App\Console\Commands;

use App\Models\CreditProduct;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\StripeClient;

class MakeStripeCreditProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-stripe-credit-products';

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
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Retrieve all prices for the specified product
        $prices = \Stripe\Price::all(['product' => 'prod_OmpATdYwOfFktX', 'limit' => 10]);

        foreach ($prices as $price) {
            if(!CreditProduct::where('price', $price->id)->first()){
                CreditProduct::create([
                    'name' => 'Twilio Credit',
                    'product_id' => 'prod_OmpATdYwOfFktX',
                    'price_id' => $price->id,
                    'price' => $price->unit_amount / 100
                ]);
            }else{
                $this->info('Already exists');
            }
        }

//        $product = Product::create([
//            'name' => 'Twilio Credit',
//            'type' => 'service',
//        ]);
//
//        for ($i = $this->option('minValue'); $i <= $this->option('maxValue'); $i++) {
//            $price = Price::create([
//                'product' => $product->id,
//                'unit_amount' => $i * 100,
//                'currency' => 'usd',
//                'recurring' => null,
//            ]);
//
//            CreditProduct::create([
//                'name' => $product->name,
//                'product_id' => $product->id,
//                'price_id' => $price->id,
//                'price' => $i
//            ]);
//        }

        $this->info('Stripe product and price created successfully.');
    }
}

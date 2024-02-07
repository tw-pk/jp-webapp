<?php

namespace App\Console\Commands;

use App\Models\CreditProduct;
use Illuminate\Console\Command;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class PopulateCreditTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-credit-table';

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

        $prices = \Stripe\Price::all(['product' => 'prod_OmpATdYwOfFktX', 'limit' => 100]);
        foreach ($prices as $price) {
            if(!CreditProduct::where('price_id', $price->id)->first()){
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
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TwilioOutboundPrices;

class TwilioCountriesPriceList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:twilio-countries-price-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonUrl = asset('twilio/twillioCountryprices.json');
        $jsonData = file_get_contents($jsonUrl);
        $data = json_decode($jsonData, true);
        foreach ($data as $key => $item) {
            
            $insert = array(
                'ISO' => $item['ISO'],
                'Country' => $item['Country'],
                'Description' => $item['Description'],
                'Price' => $item['Price']['min']
            );
            
            TwilioOutboundPrices::create($insert);
        }
        echo "Data Imported Successfully";
    }
}

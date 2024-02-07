<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Models\TwilioCountry;

class FetchTwilioCountry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-twilio-country';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fetch twilio countries list from json file';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $jsonUrl = asset('twilio/twilioCountrylist.json');
        $jsonData = file_get_contents($jsonUrl);
        $data = json_decode($jsonData, true);
        foreach ($data['countries'] as $item) {
            
            $subresource_uris_keys = NULL;
            $uris_keys = array_keys($item['subresource_uris']);
            if(!empty($uris_keys)){
                $subresource_uris_keys = implode('|', $uris_keys);
            }
            $insert = array(
                'country_code' => $item['country_code'],
                'country' => $item['country'],
                'uri' => NULL,
                'beta' => $item['beta'],
                'subresource_uris' => NULL,
                'subresource_type' => $subresource_uris_keys,
            );
            TwilioCountry::create($insert);
        }
    }
}

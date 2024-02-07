<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Country;
use App\Models\Timezone;
use App\Models\State;
use App\Models\City;

class ImportDataCountryTimezoneStateCity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-data-country-timezone-state-city';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import countries, timezone, states, cities get from json file';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $jsonUrl = asset('countries_states_cities/countries_states_cities.json');
        $jsonData = file_get_contents($jsonUrl);
        $data = json_decode($jsonData, true);


        foreach ($data as $countryData) {

            $country = Country::firstOrCreate([
                'name' => $countryData['name'],
                'code_3' => $countryData['iso3'],
                'code_2' => $countryData['iso2'],
                'numeric_code' => $countryData['numeric_code'],
                'phone_code' => $countryData['phone_code'],
                'capital' => $countryData['capital'],
                'currency' => $countryData['currency'],
                'currency_name' => $countryData['currency_name'],
                'currency_symbol' => $countryData['currency_symbol'],
                'tld' => $countryData['tld'],
                'native' => $countryData['native'],
                'region' => $countryData['region'],
                'subregion' => $countryData['subregion'],
                'latitude' => $countryData['latitude'],
                'longitude' => $countryData['longitude'],
                'emoji' => $countryData['emoji'],
                'emojiU' => $countryData['emojiU'],
            ]);

            // foreach ($countryData['timezones'] as $timezoneData) {

            //     $timezone = $country->timezones()->create([
            //         'zoneName' => $timezoneData['zoneName'],
            //         'gmtOffset' => $timezoneData['gmtOffset'],
            //         'gmtOffsetName' => $timezoneData['gmtOffsetName'],
            //         'abbreviation' => $timezoneData['abbreviation'],
            //         'tzName' => $timezoneData['tzName'],
            //     ]);
            // }

            foreach ($countryData['states'] as $stateData) {
                $state = $country->states()->create([
                    'name' => $stateData['name'],
                    'state_code' => $stateData['state_code'],
                    'type' => $stateData['type'],
                ]);

                // foreach ($stateData['cities'] as $cityData) {
                //     $state->cities()->create([
                //         'name' => $cityData['name'],
                //         'latitude' => $cityData['latitude'],
                //         'longitude' => $cityData['longitude'],
                //     ]);
                // }
            }
        }
        $this->info('Data imported successfully.');
    }
}

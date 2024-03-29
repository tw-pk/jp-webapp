<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TwilioCountry;
use App\Models\Country;
use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CountryController extends Controller
{

    public function twilio_country_list()
    {
        $t_country_list = TwilioCountry::select('country', 'country_code')->get()->toArray();

        $twilioCountry = $this->get_default_country($t_country_list);
        return response()->json([
            'message' => 'Twilio Country fetched successfully',
            'twilioCountry' => $twilioCountry
        ]);
    }

    public function get_default_country($t_country_list = null)
    {
        //$ip = '162.159.24.227';//usa
        //$ip = '103.239.147.187';//ind
        //$ip = '39.53.66.207';//pak
        $ip = request()->ip();
        $currentUserInfo = Location::get($ip);

        if (!empty($currentUserInfo->countryName)) {
            $countryName = $currentUserInfo->countryName;
            $countryCode = $currentUserInfo->countryCode;
            $key = array_search($countryName, array_column($t_country_list, 'country'));
            if ($key !== false) {
                $item = $t_country_list[$key];
                unset($t_country_list[$key]);
                array_unshift($t_country_list, $item);
            } else {

                $newCountry = [
                    "country" => $countryName,
                    "country_code" => $countryCode,
                ];
                array_unshift($t_country_list, $newCountry);
            }
        } else {
            $key = array_search("US", array_column($t_country_list, 'country_code'));
            $item = $t_country_list[$key];
            unset($t_country_list[$key]);
            array_unshift($t_country_list, $item);
        }
        foreach ($t_country_list as $key => $val) {
            $code = Str::lower($val['country_code']);
            $t_country_list[$key]['flag_url'] = asset('images/flags/' . $code . '.png');
        }
        return $t_country_list;
    }

    public function fetch_countries()
    {
        $countries  = Country::select('id', 'name', 'code_2','phone_code','emoji')->get()->toArray();
        return response()->json([
            'message' => 'Countries fetched successfully',
            'countries' => $countries
        ]);
    }

    public function fetch_dialer_countries()
    {
        $countries  = Country::select('id', 'name', DB::raw('LOWER(code_2) as code_2'), 'phone_code')->get()->toArray();
        return response()->json([
            'message' => 'Countries fetched successfully',
            'countries' => $countries
        ]);
    }

    public function fetch_state(Request $request)
    {
        $countryId = $request->input('country_id');
        $country = Country::find($countryId);
        $states = $country->states()->select('id', 'country_id', 'name', 'state_code')->get();
        return response()->json([
            'message' => 'State fetched successfully',
            'states' => $states
        ]);
    }
}

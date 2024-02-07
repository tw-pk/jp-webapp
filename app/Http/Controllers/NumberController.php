<?php

namespace App\Http\Controllers;

use App\Models\TwilioCountry;
use App\Models\UserNumber;
use App\Services\PhoneNumberCapabilities;
use Illuminate\Http\Request;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Auth;

class NumberController extends Controller
{
    public Client $twilio;

    /**
     * @throws ConfigurationException
     */
    public function __construct()
    {

        // Your Account SID and Auth Token from twilio.com/console
        $this->sid = config('app.TWILIO_CLIENT_ID');
        $this->token = config('app.TWILIO_AUTH_TOKEN');

        /** Initialize the Twilio client so it can be used */
        $this->twilio = new Client($this->sid, $this->token);
    }


    /**
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function list(Request $request)
    {

        $request->validate([
            'country' => 'required',
            'type' => 'required'
        ]);
        try {
            $params = array();

            $type = $request->type;
            $startsWith = $request->startsWith;
            $searchNumber = $request->searchNumber;
            $address_requirements = $request->address_requirements;

            //Starts With Match to Number
            if (!empty($searchNumber) && $startsWith == 'Anywhere') {
                $params['Contains'] = $searchNumber;
            }

            //Search number
            if (!empty($searchNumber) && $startsWith != 'Anywhere') {
                $params[$startsWith] = $searchNumber;
            }

            //part address Requirements
            if ($address_requirements == 'Any') {
                $params['ExcludeAllAddressRequired'] = true;
                $params['ExcludeLocalAddressRequired'] = true;
                $params['ExcludeForeignAddressRequired'] = true;
            }
            if ($address_requirements == 'None') {
                $params['ExcludeAllAddressRequired'] = false;
                $params['ExcludeLocalAddressRequired'] = false;
                $params['ExcludeForeignAddressRequired'] = false;
            }
            if ($address_requirements == 'Exclude Local') {
                $params['ExcludeLocalAddressRequired'] = true;
            }
            if ($address_requirements == 'Exclude Foreign') {
                $params['ExcludeForeignAddressRequired'] = true;
            }
            $params['address_requirements'] = $address_requirements;

            //part Capabilities



            $params['smsEnabled'] = true;
            $params['mmsEnabled'] = true;
            $params['voiceEnabled'] = true;
            $params['faxEnabled'] = true;


            //call twilio apis
            if ($type == 'All') {
                $local = $this->twilio->availablePhoneNumbers($request->country)
                    ->local
                    ->read($params, 50, 50);
            } else {
                $local = $this->twilio->availablePhoneNumbers($request->country)
                    ->$type
                    ->read($params, 50, 50);
            }
            $numbers = [];
            foreach ($local as $record) {

                $numbers[] = [
                    'numbers' => $record->phoneNumber,
                    'type' => $type,
                    'capabilities' => [
                        'sms' => $params['smsEnabled'],

                        'mms' => $params['mmsEnabled'],

                        'voice' => $params['voiceEnabled'],
                    ],
                    'addressRequirements' => $address_requirements,
                    'region' => $record->region,
                    'city' => $record->locality,
                    'button' => $record->phoneNumber,
                    'isDisabled' => false
                ];
            }

            if (count($numbers)) {
                return response()->json([
                    'message' => 'Numbers fetched successfully',
                    'numbers' => $numbers
                ]);
            } else {
                return response()->json([
                    'message' => 'Numbers fetched successfully',
                    'numbers' => $numbers
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    private function searchNumbers()
    {
        return $this->twilio_client->availablePhoneNumbers->read(12);
    }


    public function purchaseNumber(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required'
        ]);

        if (UserNumber::where('user_id', Auth::user()->id)->count() > 4) {
            return response()->json(['message' => 'Maximum 3 number allowed during trial, you can get more numbers after update']);
        }
        $phoneNumber = $request->input('phoneNumber');
        try {
            $allDigits = preg_replace('/\D/', '', $phoneNumber);
            $smsUrl = route('sms', ['number' => $allDigits]);
            $voiceUrl = route('voice.incoming', ['number' => $allDigits]);
            $incomingPhoneNumbers = $this->twilio->incomingPhoneNumbers
                ->create([
                    'phoneNumber' => $phoneNumber,
                    'smsUrl' => $smsUrl,
                    'voiceUrl' => $voiceUrl,
                ]);

            $phoneNumberSid = $incomingPhoneNumbers->sid;
            $this->storeNumber($phoneNumber, $request->state, $request->city, $phoneNumberSid);
            return response()->json([
                'message' => "You have successfully purchased $phoneNumber",
                'number' => $phoneNumber
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * @throws TwilioException
     */
    public function storeNumber($number, $state = null, $city = null, $phoneNumberSid = null)
    {
        try {
            $twilio_number_lookup = $this->twilio->lookups->v2->phoneNumbers($number)->fetch();
            UserNumber::create([
                'user_id' => Auth::user()->id,
                'phone_number' => $number,
                'phone_number_sid' => $phoneNumberSid,
                'country_code' => $twilio_number_lookup->countryCode,
                'country' => TwilioCountry::where('country_code', $twilio_number_lookup->countryCode)->first()->country ?? $twilio_number_lookup->country,
                'state' => $city,
                'city' => $state
            ]);
        } catch (TwilioException $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function existingNumbers(){
        $numbers = Auth::user()->numbers;
        $userNumbers = [];
        if($numbers->count()){
            foreach ($numbers as $number) {
                $userNumbers[] = [
                    'id' => $number->id,
                    'phone_number' => $number->phone_number
                ];
            }
        }
        return response()->json([
            'status' => true,
            'numbers' => $userNumbers
        ]);
    }


    public function handleSms($id)
    {
        dd($id);
    }

    public function handleVoice($id)
    {
        dd($id);
    }
}

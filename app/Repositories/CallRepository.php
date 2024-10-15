<?php

namespace App\Repositories;

use App\Models\Call;
use App\Models\UserCredit;
use App\Models\User;
use Twilio\Rest\Client;
use Carbon\Carbon;
use App\Jobs\FetchCallPriceJob;
use DB;
use libphonenumber\PhoneNumberUtil;


class CallRepository implements CallRepositoryInterface
{

    public function getAllCalls()
    {
        return Call::all();
    }

    public function getCallById($id)
    {
        return Call::find($id);
    }

    public function saveCall(array $data)
    {

        $priceDetail = $this->getNumberPrice($data['To']);
        
        try {                        
            DB::table('calls')->insert([  
                'sid' => $data['CallSid'],  
                'to' => $data['To'],          
                'from' => $data['Caller'],     
                'status' => $data['CallStatus'],  
                'duration' => 0,     
                'direction' => $data['Direction'],  
                'date_time' => null,    
                'user_id' => $data['agent'],     
                'contact_id' => null,  
                'created_at' => now(),     
                'updated_at' => now(),     
                'price' => $priceDetail['mobileLegPrice'],          
                "country_price" => $priceDetail['countryPrice'],                
                'conference_name' => null, 
                'mobile_call_sid' => null  
            ]);           
    
        } catch (\Exception $th) {
            return $th->getMessage();
        }        
        
    }


    public function getNumberPrice($phoneNumber)
    {

        if (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+' . $phoneNumber;
        }
        
        $phoneUtil = PhoneNumberUtil::getInstance();    

        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);

        // Parse the number
        $numberProto = $phoneUtil->parse($phoneNumber, null);            
        $countryCode = $numberProto->getCountryCode();            
        $regionCode = $phoneUtil->getRegionCodeForCountryCode($countryCode);

        // Fetch the pricing information
        $number = $twilio->pricing->v2->voice->numbers($phoneNumber)->fetch();                
        $country = $twilio->pricing->v2->voice->countries($regionCode)->fetch();

        // Get prices
        $mobileLegPrice = $number->outboundCallPrices[0]['current_price'];
        $dialerLegPrice = $country->outboundPrefixPrices[2]['current_price'];

        // Prepare the data array
        $data = [
            'mobileLegPrice' => $mobileLegPrice,
            'countryPrice'   => $dialerLegPrice
        ];

        return $data;            
    }





    public function updateCall(array $data)
    {                          
        
        try {
            
            $callDetail = Call::where('sid', $data['CallSid'])->first();                

            if (!$callDetail) {
                throw new \Exception('Call details not found for CallSid: ' . $data['CallSid']);
            }
        
            $mobileDetails = $this->fetchCallSidDetails($callDetail->mobile_call_sid);
            $forwradCallDetails = $this->fetchCallSidDetails($callDetail->forwarded_call_sid);                        
            
            $totalCallDuration = $data['CallDuration'];
            $totalPriceWithMargin = 0;

            if(!empty($forwradCallDetails))
            {
                $totalPriceWithMargin = $this->calCulateTotalPrice($totalCallDuration, $callDetail->country_price, $callDetail->price, $callDetail->forward_call_price, $forwradCallDetails['duration']);
            }else{
                if($mobileDetails['duration'] == '0'){
                    $totalPriceWithMargin = $this->calCulateTotalPrice($totalCallDuration, $callDetail->country_price, 0, 0, 0);
                }else{
                    $totalPriceWithMargin = $this->calCulateTotalPrice($totalCallDuration, $callDetail->country_price, $callDetail->price, 0, 0);
                }
            }            

            DB::beginTransaction();
                    
            $updateCallDetails = Call::where('sid', $data['CallSid'])->update([
                'status'        =>  $data['CallStatus'],
                'duration'      =>  $data['CallDuration'],
                'date_time'     =>  $data['Timestamp'],
                'total_price'   =>  $totalPriceWithMargin,
                'forward_call_duration' => $forwradCallDetails['duration']
            ]);
        
            if (!$updateCallDetails) {
                throw new \Exception('Failed to update call details for CallSid: ' . $data['CallSid']);
            }
                
            $user = User::with('invitations')->where('id', $callDetail->user_id)->first();
        
            if (!$user) {
                throw new \Exception('User not found with ID: ' . $callDetail->user_id);
            }        
            // Determine the user ID to charge (Admin or inviter)
            $userIdToCharge = $user->hasRole('Admin') ? $user->id : $user->invitations->user_id;        
            // Deduct admin credit
            $deductAdminPrice = $this->deductAdmiCredit($userIdToCharge, $totalPriceWithMargin);                                
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();            
            return $e->getMessage();
        }
        
    }


    public function updateMobileCallSid($identifier, $mobileCallSid)
    {        
        $call = Call::where('sid', $identifier)->first();                
        if ($call) {
            $call->mobile_call_sid = $mobileCallSid;
            $call->save();
        }
        
        return $call;
    }

    public function fetchCallSidDetails($callSid)
    {
        $client = new Client(config('app.TWILIO_CLIENT_ID'), config('app.TWILIO_AUTH_TOKEN'));
        $call = $client->calls($callSid)->fetch();             
        $startTime = $call->startTime ? $call->startTime->format('Y-m-d H:i:s') : null;
        $endTime = $call->endTime ? $call->endTime->format('Y-m-d H:i:s') : null;
        $duration = $call->duration;
        $status = $call->status;        

        $dateTime = $startTime . '-' . $endTime;
        
        $updatedData = [
            'duration' => $duration,
            'status' => $status,
            'date_time' => $dateTime,
        ];

        return $updatedData;
    }

    public function deductAdmiCredit($adminId, $price)
    {
        try {            

            $adminCredit = UserCredit::where('user_id', $adminId)->first();                        
            $adminCredit->credit -=  $price;                         
            $result = $adminCredit->save();            
            return $result;
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);            
        }
    }


    public function calCulateTotalPrice($durationInSeconds, $countryPriceRate, $numberPriceRate, $forwardNumberPrice, $forwardCallDuration) 
    {

        $minutes = ceil($durationInSeconds / 60);                 
        $forwardCallMinutes = ceil($forwardCallDuration / 60);
        // Calculate the total cost for the country and mobile number
        $countryPrice = $minutes * $countryPriceRate;  
        $mobilePrice = $minutes * $numberPriceRate;   
        $forwardCallPrice = $forwardCallMinutes * $forwardNumberPrice;        
        // Calculate the combined price
        $totalPrice = $countryPrice + $mobilePrice + $forwardCallPrice;        
        
        // Add 15% margin
        $margin = 0.10;
        $totalPriceWithMargin = $totalPrice + ($totalPrice * $margin);                
        // Return the total price with margin
        return $totalPriceWithMargin;
    }

    public function saveForwardCallDetail(string $dialerCallSid, string $forwardNumber)

    {
        $call = Call::where('sid', $dialerCallSid)->first();          
        $price = $this->getNumberPrice($forwardNumber);               
        $call->forwarded_to = $forwardNumber;
        $call->forward_call_price = $price['mobileLegPrice'];
        $call->save();
    }

    public function saveForwardCallSid(string $dialerCallSid, string $forwradCallSid)
    {        
        $call = Call::where('sid', $dialerCallSid)->first();        
        $call->forwarded_call_sid = $forwradCallSid;
        $call->save();
    }


}

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
                'duration' => 0 .' seconds',     
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

    public function updateCallDirection(string $callSid)
    {
        $call = Call::where('sid', $callSid)->first();
        $call->direction = 'outbound-dial';
        $call->save();
    }

    
    public function updateCall(array $data)
    {
        try {
            // Fetch call details by CallSid
            $callDetail = Call::where('sid', $data['CallSid'])->first();

            if (!$callDetail) {
                throw new \Exception('Call details not found for CallSid: ' . $data['CallSid']);
            }

            // Fetch mobile and forwarded call details based on SIDs stored in the call record
            $mobileDetails = $this->fetchCallSidDetails($callDetail->mobile_call_sid);
            $forwardCallDetails = $callDetail->forwarded_call_sid 
                                ? $this->fetchCallSidDetails($callDetail->forwarded_call_sid) 
                                : null;            

            // Total call duration from the provided data
            $totalCallDuration = (int) $data['CallDuration']; // Ensure duration is an integer
            $totalPriceWithMargin = 0;
            
            // Case 1: Mobile call duration is 0
            if ((int) $mobileDetails['duration'] === 0) {
                $totalPriceWithMargin = $this->calCulateTotalPrice(
                    $totalCallDuration, 
                    $callDetail->country_price, 
                    0, 0, 0
                );

                \Log::info("Price for zero duration: " . $totalPriceWithMargin);
            }
             else {
                // Case 2: Forward call details exist
                if ($forwardCallDetails) {
                    $totalPriceWithMargin = $this->calCulateTotalPrice(
                        $totalCallDuration, 
                        $callDetail->country_price, 
                        $callDetail->price, 
                        $callDetail->forward_call_price, 
                        $forwardCallDetails['duration']
                    );

                    \Log::info("Price with forward call details: " . $totalPriceWithMargin);
                } else {
                    // Case 3: No forward call details, only calculate based on base and mobile details
                    $totalPriceWithMargin = $this->calCulateTotalPrice(
                        $totalCallDuration, 
                        $callDetail->country_price, 
                        $callDetail->price, 
                        0, 0
                    );

                    \Log::info("Price without forward call: " . $totalPriceWithMargin);
                }
            }

            // Start transaction to ensure data integrity
            DB::beginTransaction();

            // Update the call details in the database
            $updateCallDetails = Call::where('sid', $data['CallSid'])->update([
                'status' => $data['CallStatus'],
                'duration' => $data['CallDuration'] . ' seconds',
                'date_time' => $data['Timestamp'],
                'total_price' => $totalPriceWithMargin,
                'forward_call_duration' => $forwardCallDetails['duration'] ?? null
            ]);

            // Log the result of the update operation
            \Log::info("Updated call details: " . json_encode($updateCallDetails));

            if (!$updateCallDetails) {
                throw new \Exception('Failed to update call details for CallSid: ' . $data['CallSid']);
            }

            // Fetch the related user for this call
            $user = User::with('invitations')->where('id', $callDetail->user_id)->first();

            if (!$user) {
                throw new \Exception('User not found with ID: ' . $callDetail->user_id);
            }

            // Determine whether to charge the Admin or the inviter
            $userIdToCharge = $user->hasRole('Admin') 
                            ? $user->id 
                            : $user->invitations->user_id;

            \Log::info("User ID to charge: " . $userIdToCharge);

            // Deduct admin credit based on the calculated total price
            $deductAdminPrice = $this->deductAdmiCredit($userIdToCharge, $totalPriceWithMargin);

            if (!$deductAdminPrice) {
                throw new \Exception('Failed to deduct admin credit.');
            }

            // Commit transaction if everything went fine
            DB::commit();

            return "Call updated successfully.";

        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();

            // Log the error message and return the error message
            \Log::error("Error updating call: " . $e->getMessage());
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

        \Log::info("here is update data =>", $updatedData);
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

        if ($forwardNumberPrice > 0 && $forwardCallDuration == 0) {
            $forwardCallDuration = 60;
        }

        $minutes = ceil($durationInSeconds / 60);                 
        $forwardCallMinutes = ceil($forwardCallDuration / 60);        
        
        $countryPrice = $minutes * $countryPriceRate;  
        $mobilePrice = $minutes * $numberPriceRate;   
        $forwardCallPrice = $forwardCallMinutes * $forwardNumberPrice;        

        $totalPrice = $countryPrice + $mobilePrice + $forwardCallPrice;                        
        
        // Add 10% margin
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

<?php

namespace App\Repositories;

use App\Models\Call;
use Twilio\Rest\Client;
use Carbon\Carbon;
use App\Jobs\FetchCallPriceJob;


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
        // $numberPrice = $this->getNumberPrice($data['To']);
        $numberPrice = 0.023;

        $startTime = $data['startTime'] ? Carbon::parse($data['startTime'])->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A') : Carbon::now()->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A');
        $endTime = $data['endTime'] ? Carbon::parse($data['endTime'])->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A') : Carbon::now()->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A');
        $dateTime = "From " . $startTime . " - To " . $endTime;

        $createData = [
            'sid'       =>    $data['CallSid'],
            'to'        =>    $data['To'],
            'from'      =>    $data['Caller'],
            'user_id'   =>    $data['agent'],
            'duration'  =>    0,
            'status'    =>    $data['CallStatus'],
            'direction' =>    $data['Direction'],
            'date_time' =>    $dateTime,
            'contact_id'=>    null,
            'price'     =>    $numberPrice

        ]; 

        \Log::info("here is cretaed data =>". $createData);

        $result = Call::insert($createData);
        \Log::info("Here is the call created successfully =>". $result);
        return $result;
    }


    public function getNumberPrice($phoneNumber)
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        
        $number = $twilio->pricing->v2->voice->numbers($phoneNumber)->fetch();
        
        $price = isset($number->inboundCallPrice['current_price']) 
        ? json_encode($number->inboundCallPrice['current_price']) 
        : (isset($number->outboundCallPrices[0]['current_price']) 
            ? json_encode($number->outboundCallPrices[0]['current_price']) 
            : null);                

        return $price;            
    }

    public function updateCall(array $data)
    {                          
        // \Log::info("here is the update call function =>". print_r($data, true));
        FetchCallPriceJob::dispatch($data['childCallSid'], 5);
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
        $price = $call->price;
        $dateTime = $startTime . '-' . $endTime;
        
        $updatedData = [
            'duration' => $duration . " seconds",
            'status' => $status,
            'date_time' => $dateTime,
        ];
        return $updatedData;
    }

    public function fetchUserCredit($adminId, $price)
    {
        try {
            
            $adminCredit = UserCredit::where('user_id', $adminId)->first();
            
            $callPrice = $price;
            $priceWithMargin = $callPrice * 1.15;
            $adminCredit->credit -=  $priceWithMargin; 
            $adminCredit->save();
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);            
        }
    }
}

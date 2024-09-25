<?php

namespace App\Repositories;

use App\Models\Call;
use Twilio\Rest\Client;


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
        $createData = [
            'sid'       =>    $data['CallSid'],
            'to'        =>    $data['To'],
            'from'      =>    $data['Caller'],
            'user_id'   =>    $data['agent'],
            'duration'  =>    0,
            'status'    =>    $data['CallStatus'],
            'direction' =>    $data['Direction'],
            'date_time' =>    now(),
            'contact_id'=>    null,
            'price'     =>    $numberPrice

        ]; 

        \Log::info("here is cretaed data =>". $createData);

        $result = Call::insert($createData);
        \Log::info("Here is the call created successfully =>". $result);
        return $result;
    }


    public function getNumberPrice($n)
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        
        $number = $twilio->pricing->v2->voice->numbers($n)->fetch();
        
        $price = isset($number->inboundCallPrice['current_price']) 
        ? json_encode($number->inboundCallPrice['current_price']) 
        : (isset($number->outboundCallPrices[0]['current_price']) 
            ? json_encode($number->outboundCallPrices[0]['current_price']) 
            : null);
        
        return $price;
    }
}

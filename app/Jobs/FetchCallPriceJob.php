<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use App\Models\Call;

class FetchCallPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $callSid;
    protected $maxAttempts;
    protected $twilio;

    /**
     * Create a new job instance.
     *
     * @param string $callSid
     * @param int $maxAttempts
     */
    public function __construct($callSid, $maxAttempts = 5)
    {
        $this->callSid = $callSid;
        $this->maxAttempts = $maxAttempts;
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $this->twilio = new Client($sid, $token);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        

        $call = $this->twilio->calls($this->callSid)->fetch();

        \Log::info("here is the call details =>". print_r($call));

        if ($call->duration > 0 && !is_null($call->price)) {
            // $price = $call->price;
            Log::info("Call SID: {$this->callSid}, Duration: {$call->duration}, Price: {$price}");

            $this->updateCallPriceInDatabase($this->callSid, $call);
        } else {
            Log::info("Call SID: {$this->callSid} has no price yet, retrying...");

            if ($this->maxAttempts > 0) {
                $this->retryJob();
            } else {
                Log::info("Max attempts reached for Call SID: {$this->callSid}, stopping retries.");
            }
        }
    }

    /**
     * Retry the job with a delay.
     */
    protected function retryJob()
    {
        // Retry the job with a delay of 5 minutes
        FetchCallPriceJob::dispatch($this->callSid, $this->maxAttempts - 1)
            ->delay(now()->addMinutes(5));
    }

    /**
     * Update the call price in the database.
     */
    protected function updateCallPriceInDatabase($callSid, $data)
    {
        $parentCallSid = Call::where('mobile_call_sid', $callSid)->pluck('sid');
        \Log::info("here is the parent call sid =>". $parentCallSid);
        // $call = $this->twilio->calls($parentCallSid)->fetch();
        // $price = $call->price;
        // \Log::info("here is the parent call price =>". $price);
        // $priceWithMargin = $price + ($price * 0.20);                
        Call::where('mobile_call_sid', $callSid)->update([
            'price' => $priceWithMargin,
            'status'=> $data->status,
            'duration'=> $data->duration,
        ]);
    }
}

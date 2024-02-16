<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Twilio\Rest\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchTwilioMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
       
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = User::all();
        $twilio = new Client(env('TWILIO_CLIENT_ID'), env('TWILIO_AUTH_TOKEN'));
        //foreach all users
        //check whether a user is admin or not
        foreach ($users as $user) {
            if ($user->hasRole('Admin')) {
                $numbers = $user->numbers;
                
                foreach ($numbers as $number) {
                    $twilioCallsIncoming = $twilio->calls->read(['to' => $number->phone_number], 100);
                    $twilioCallsOutgoing = $twilio->calls->read([ 'from' => $number->phone_number], 100);
                    // Merged results
                    $twilioCalls = array_merge($twilioCallsIncoming, $twilioCallsOutgoing);
                    foreach ($twilioCalls as $call) {
                        if(!Str::startsWith($call->from, 'client') && !Str::startsWith($call->to, 'client')){
                            if(!Call::where('sid', $call->sid)->first()){
                                Call::create([
                                    'sid' => $call->sid,
                                    'from' => $call->from,
                                    'to' => $call->to,
                                    'user_id' => $user->id,
                                    'contact_id' => Contact::where('phone', $call->to)->first()?->id,
                                    'date_time' => "From ". Carbon::parse($call->startTime)->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A')." - To ".Carbon::parse($call->endTime)->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A'),
                                    'duration' => $call->duration . " seconds" ?? '-',
                                    'direction' => $call->direction,
                                    'status' => $call->status
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
}

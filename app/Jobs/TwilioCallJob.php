<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TwilioServices;
use Illuminate\Support\Facades\Log;

class TwilioCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $callTypeTab;
    /**
     * Create a new job instance.
     */
    public function __construct($callTypeTab)
    {
        $this->callTypeTab = $callTypeTab;
    }

    /**
     * Execute the job.
     */
    public function handle(TwilioServices $TwilioServices): void
    {
        try {
            $calls = $TwilioServices->makeTwilioCall($this->callTypeTab);
            // Handle the Twilio API response as needed
        } catch (\Exception $e) {
            Log::error('Twilio API error: ' . $e->getMessage());
        }
    }
}

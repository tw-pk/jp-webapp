<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchTwilioMessagesJob;

class RunFetchTwilioMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-fetch-twilio-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        dispatch(new FetchTwilioMessagesJob());
        $this->info('FetchTwilioMessages job dispatched successfully.');
    }
}

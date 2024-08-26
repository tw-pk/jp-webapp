<?php

namespace App\Console;

use App\Jobs\FetchTwilioCallsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Auth;
use App\Services\AutoTopUpPaymentService;
use App\Models\User;
use App\Models\CreditProduct;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new FetchTwilioCallsJob())
                ->everyFiveMinutes()
                //->everyMinute()
                ->name('fetch-twilio-calls-job')
                ->withoutOverlapping();
                 
        $schedule->call(function () {
            $service = new AutoTopUpPaymentService();

            User::with('credit')->get()->each(function ($user) use ($service) {
                // Ensure the user has a credit entry
                if ($user->credit && $user->stripe_id) {
                    $thresholdValue = CreditProduct::where('price_id', $user->credit->threshold_value)
                        ->pluck('price')
                        ->first();

                    // Check if the user's credit is below the threshold value
                    if ($user->credit->credit < $thresholdValue && $user->credit->threshold_enabled == '1') {
                        $service->checkAndTopUp($user);
                    }
                }
            });
        })->daily()
          ->name('auto-top-up')
          ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

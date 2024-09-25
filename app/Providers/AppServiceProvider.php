<?php

namespace App\Providers;

use App\Http\Controllers\Api\VoiceController;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Twilio\Rest\Client;
use App\Repositories\CallRepository;
use App\Repositories\CallRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TwoFactorAuthenticationService::class, function ($app) {
            return new TwoFactorAuthenticationService(new Client(
                config('app.TWILIO_CLIENT_ID'),
                config('app.TWILIO_AUTH_TOKEN')
            ));
        }, CallRepositoryInterface::class , CallRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(User::class);
    }
}

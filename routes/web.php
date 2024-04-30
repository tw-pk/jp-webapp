<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NumberController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TwilioController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('{any?}', function () {
    return view('application');
})->where('any', '.*');

Route::get('/reset-password/{token}', [AuthController::class, 'resetPasswordToken'])->middleware('guest')->name('password.reset');

Route::stripeWebhooks('stripe/webhook');

Route::post('voice/dial', [TwilioController::class, 'dial']);
Route::post('voice/incoming-{number}', [TwilioController::class, 'incomingCall'])->name('voice.incoming');

Route::post('webhook/ten/dlc', [TwilioController::class, 'webhook_ten_dlc'])->name('webhook.ten.dlc');

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

//These urls are passed to twilio and they cannot be changed 
Route::post('/sms-{number}', [NumberController::class, 'handleSms'])->name('sms');
Route::post('/voice-{number}', [NumberController::class, 'handleVoice'])->name('voice');

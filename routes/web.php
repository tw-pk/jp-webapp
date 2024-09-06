<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NumberController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\CallController;
use Twilio\TwiML\VoiceResponse;
use App\Http\Controllers\ConferenceController;


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

Route::post('voice/dial', [TwilioController::class, 'dial']);

Route::post('voice/incoming-{number}', [TwilioController::class, 'incomingCall'])->name('voice.incoming');

Route::post('webhook/ten/dlc', [TwilioController::class, 'webhook_ten_dlc'])->name('webhook.ten.dlc');

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::post('/handle-recording', [TwilioController::class, 'handleRecording'])->name('handle-recording');
Route::post('/handle-transcription', [TwilioController::class, 'handleTranscription'])->name('handle-transcription');

//These urls are passed to twilio and they cannot be changed 
Route::post('/sms-{number}', [NumberController::class, 'handleSms'])->name('sms');
Route::post('/voice-{number}', [NumberController::class, 'handleVoice'])->name('voice');

//new croute to make calls
Route::post('/make-call', [CallController::class, 'makeCall']);

Route::post('/get-call-info', [CallController::class, 'getCallInfo'])->name('get-call-info');

Route::post('/place-on-hold', [CallController::class, 'placeOnHold']);

Route::post('/twiml/new-url', [CallController::class, 'holdTwiML'])->name('hold-url');

Route::post('/resume-from-hold', [CallController::class, 'resumeCall'])->name('resume-url');

Route::post('/twiml/continue-conversation', [CallController::class, 'continueConversation'])->name('continue-conversation');

Route::post('/create-conference', [ConferenceController::class, 'createConference']);

Route::post('/twilio/conference-status-callback',  [ConferenceController::class, 'conferenceStatusCallback'])->name('twilio.conferenceStatusCallback');
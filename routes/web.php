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

Route::post('/get-mobile-callsid', [CallController::class, 'mobileCallLeg'])->name('get-mobile-callsid');

Route::post('/get-call-info', [CallController::class, 'getCallInfo'])->name('get-call-info');

Route::post('/check-call-status', [CallController::class, 'checkCallStatus'])->name('check-call-status');

Route::post('twiml/place-on-hold', [CallController::class, 'placeOnHold']);

Route::post('/twiml/hold-url', [CallController::class, 'holdTwiML'])->name('hold-url');

Route::post('/twiml/resume-from-hold', [CallController::class, 'resumeCall'])->name('resume-url');

Route::post('/twiml/continue-conversation', [CallController::class, 'continueConversation'])->name('continue-conversation');

Route::post('/twiml/create-conference', [ConferenceController::class, 'createConference']);

Route::post('/twiml/join-conference', [ConferenceController::class, 'joinConference'])->name('join-conference');

Route::post('/twilio/conference-status-callback',  [ConferenceController::class, 'conferenceStatusCallback'])->name('twilio.conferenceStatusCallback');

Route::post('/twiml/transfer-call', [CallController::class, 'transferCall'])->name('transfer-call');

Route::post('/twiml/forward-ringing', [CallController::class, 'forwardRinging'])->name('forward-ringing');

Route::post('/twiml/transfer-call-conference', [CallController::class, 'transferCallConference'])->name('transfer-call-conference');

Route::post('/twiml/connect-transfer-call', [CallController::class, 'connectTransferCall'])->name('connect-transfer-call');
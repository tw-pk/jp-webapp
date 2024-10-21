<?php

use App\Http\Controllers\Api\MembersController;
use App\Http\Controllers\Api\TwoFactorAuthController;
use App\Http\Controllers\Api\VoiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NumberController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PhoneController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\DialerSettingController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ApexChartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->post('broadcasting/auth', function (Request $request) {

    $pusher = new Pusher\Pusher(
        config('app.PUSHER_APP_KEY'),
        config('app.PUSHER_APP_SECRET'),
        config('app.PUSHER_APP_ID'),
        ['cluster' => config('app.PUSHER_APP_CLUSTER')]
    );
    return $pusher->socketAuth($request->request->get('channel_name'), ($request->request->get('socket_id')));
});

//'middleware' => ['check.balance']
Route::group(['prefix' => 'auth'], function (){
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register']);
    Route::post('/forgot/password', [AuthController::class, 'forgotPassword']);

    Route::post('/verify-reset-link', [AuthController::class, 'verifyResetLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('invitation/verify', [MembersController::class, 'verifyToken']);
    
    Route::post('verify-phone-number', [AuthController::class, 'verifyPhoneNumber']);
    Route::post('verify-code', [AuthController::class, 'verifyCode']);

    Route::group(['middleware' => ['auth:sanctum']], function () {

        //twilio secondary profile
        Route::post('create/profile/ten-dlc', [AuthController::class, 'create_ten_dlc']);
        Route::post('delete/profile/ten-dlc', [AuthController::class, 'delete_ten_dlc']);

        //Auth routes
        Route::post('/email/resend', [VerificationController::class, 'resend']);
        Route::post('/email/verify', [VerificationController::class, 'verify']);
        Route::get('logout', [AuthController::class, 'logout']);
        Route::post('/user', [AuthController::class, 'user']);

        //checkout session generator for first checkout
        Route::post('/create-session-details', [StripeController::class, 'createCheckoutSession']);

        // create checkout after subscription expired
        Route::post('/create-subscription-checkout', [StripeController::class, 'createSubscriptionCheckout']);

        //profile data api
        Route::post('/user/profile/data', [AuthController::class, 'userProfileData']);
        Route::post('user/account/deactivate', [AuthController::class, 'accountDeactivate']);
        Route::post('/user/profile/update', [AuthController::class, 'updateProfile']);
        Route::post('/user/role', [AuthController::class, 'isRole']);

        // update password api
        Route::patch('/password/update', [AuthController::class, 'updatePassword']);

        //Two-Factor Authentication APIs
        Route::post('/2fa/enable', [TwoFactorAuthController::class, 'generateOtp']);
        Route::post('/2fa/verify-code', [TwoFactorAuthController::class, 'verifyOtp']);

        Route::post('/2fa/verify-session', [TwoFactorAuthController::class, 'verifySession']);
        Route::post('/2fa/profile', [TwoFactorAuthController::class, 'twoFactorProfile']);
        Route::post('/2fa/generate-code', [TwoFactorAuthController::class, 'generateCode']);
        Route::post('/2fa/resend-code', [TwoFactorAuthController::class, 'resendCode']);
        Route::post('/2fa/is-enabled', [TwoFactorAuthController::class, 'isEnabled']);
        Route::post('/2fa/disable', [TwoFactorAuthController::class, 'disable']);

        ////////
        /// Stripe Routes Start
        ///////

        //Create Stripe Session
        Route::post('/stripe/session/create', [StripeController::class, 'createSession']);
        Route::post('/stripe/payment-intent/create', [StripeController::class, 'createPaymentIntent']);
        Route::post('/stripe/session/top-up/create', [StripeController::class, 'createTopUpSession']);
        Route::post('/stripe/intent/top-up/create', [StripeController::class, 'createTopUpSessionIntent']);
        Route::post('/stripe/payment-methods', [StripeController::class, 'paymentMethods']);
        Route::post('/stripe/default-payment-method-check', [StripeController::class, 'defaultPaymentMethod']);

        //Check Stripe Subscription
        Route::post('subscription/check', [StripeController::class, 'checkUserSubscription']);
        Route::post('subscription/plan', [StripeController::class, 'getPlanDetails']);
        Route::get('/invoices', [StripeController::class, 'invoices']);


        ////////
        /// End Stripe Routes
        ///////

        //fetch twilio country from a database
        Route::post('twilio/country/list', [CountryController::class, 'twilio_country_list']);
        Route::post('twilio/capability/token', [TwilioController::class, 'retrieveToken']);
        Route::get('check-balance/{userId}', [TwilioController::class, 'checkBalance']);

        Route::post('numbers/list', [NumberController::class, 'list']);
        Route::post('numbers', [NumberController::class, 'existingNumbers']);
        Route::post('purchase/number', [NumberController::class, 'purchaseNumber']);
        Route::post('selected/number/payment', [StripeController::class, 'payment']);

        //fetch countries and state from a database
        Route::post('fetch/countries', [CountryController::class, 'fetch_countries']);
        Route::post('fetch/dialer/countries', [CountryController::class, 'fetch_dialer_countries']);
        Route::post('fetch/state', [CountryController::class, 'fetch_state']);

        //Manage Members
        Route::post('members/add', [MembersController::class, 'store']);
        Route::post('members/list', [MembersController::class, 'list']);
        Route::post('fetch/members', [MembersController::class, 'fetchMembers']); 
        Route::post('fetch/members-for-chart', [MembersController::class, 'fetchMembersForChart']); 
        Route::delete('member/delete/{id}', [MembersController::class, 'deleteMember']);
        
        Route::post('member/detail', [MembersController::class, 'fetchMemberDetail']);
        Route::post('connect/transfer-call', [TwilioController::class, 'transferCall']);

        //invite member routes
        Route::post('invitations/store', [InvitationController::class, 'store']);

        //Manage Team
        Route::post('team/add', [TeamController::class, 'store']);
        Route::post('team/list', [TeamController::class, 'list']);
        Route::post('team/fetch/members/teams', [TeamController::class, 'membersTeams']);
        Route::delete('team/delete/{id}', [TeamController::class, 'delete_team']);

        Route::post('team/fetch/members', [TeamController::class, 'teamFetchMembers']);
        //Route::post('team/fetch/teams', [TeamController::class, 'fetch_teams']);

        //fetch user phone number
        Route::post('fetch/user/numbers', [TeamController::class, 'fetch_numbers']);
        Route::post('fetch/roles', [TeamController::class, 'fetch_roles']);
        Route::post('user-numbers/total', [AuthController::class, 'fetch_total']);
        Route::post('numbers/owned', [TeamController::class, 'ownedActiveNumbers']);

        //Manage db phone numbers
        Route::post('fetch/number/list', [PhoneController::class, 'list']);
        Route::post('fetch/assign/number', [PhoneController::class, 'fetchAssignNumber']);
        Route::post('phone/assign', [PhoneController::class, 'phone_assign']);

        //Phone Setting
        Route::post('fetch/setting', [SettingController::class, 'fetch_setting']);
        Route::post('add/callrouting', [SettingController::class, 'add_routing']);
        Route::post('add/callerids', [SettingController::class, 'add_callerids']);
        Route::post('add/voicemail', [SettingController::class, 'add_voicemail']);

        //Contact Routes

        Route::post('fetch/contact/list', [ContactController::class, 'list']);
        Route::post('add/contact', [ContactController::class, 'add_contact']);
        Route::delete('contact/delete/{id}', [ContactController::class, 'delete_contact']);
        Route::post('contact/details', [ContactController::class, 'details']);
        Route::post('contact/fetch', [ContactController::class, 'findContact']);
        Route::post('shared/contact', [ContactController::class, 'sharedContact']);

        // Conversations routes
        Route::get('chat/chats-and-contacts', [ChatController::class, 'fetch_chats_and_contacts']);
        Route::get('chats/{chat}', [ChatController::class, 'fetch_chat']);
        Route::post('chats/send/message', [ChatController::class, 'send_message']);

        //voice routes
        Route::post('voice/call', [VoiceController::class, 'call']);

        //recent calls
        Route::post('recent-calls/list', [VoiceController::class, 'recent_calls']);
        Route::post('recent-calls-dash/list', [VoiceController::class, 'recent_calls_dash']);
        Route::post('recent-calls-contact/list', [VoiceController::class, 'recent_calls_contact']);
        Route::post('dashboard/number/analysis', [VoiceController::class, 'dashNumberAnalysis']);
        Route::post('dashboard/member-list', [VoiceController::class, 'dashMemberList']);

        //Apex chart controller
        Route::post('dashboard/live/calls', [ApexChartController::class, 'dashLiveCalls']);
        Route::post('fetch/apex-chart-report', [ApexChartController::class, 'fetchApexChartReport']);
        Route::post('fetch/statistics', [ApexChartController::class, 'fetchStatistics']);
        
        //filter recent calls
        Route::post('recent-calls/list/filter', [VoiceController::class, 'filter_recent_calls']);

        //end conversations routes

        //Notes
        Route::post('add/note', [NoteController::class, 'add_note']);
        Route::post('fetch/note', [NoteController::class, 'fetch_note']);

        //Roles
        Route::post('fetch/role', [RoleController::class, 'fetch_role']);
        Route::post('add/role', [RoleController::class, 'add_role']);
        Route::delete('role/delete/{id}', [RoleController::class, 'delete_role']);

        //Notifications
        Route::post('notifications', [NotificationController::class, 'getNotifications']);

        //Credit
        Route::post('fetch/top-up-credit', [CreditController::class, 'fetch_top_up_credit']);
        Route::post('fetch/top-up-limits', [CreditController::class, 'fetch_top_limits']);
        Route::post('add/top-up-credit', [CreditController::class, 'add_top_up_credit']);
        Route::post('credit-info/update', [CreditController::class, 'update_credit_info']);
        Route::post('fetch/credit-payment', [CreditController::class, 'fetch_credit_payment']);
        Route::post('add/credit-payment', [CreditController::class, 'add_credit_payment']);
        Route::post('stripe/payment-method/store', [StripeController::class, 'createPaymentMethod']);
        Route::post('stripe/payment-method/update', [StripeController::class, 'updatePaymentMethod']);
        Route::delete('stripe/payment-method/{id}', [StripeController::class, 'deletePaymentMethod']);

        //Dialer Routes
        Route::post('fetch/dialer/contacts', [PhoneController::class, 'dialer_contacts']);
        Route::post('dialer/setting/save', [DialerSettingController::class, 'dialer_setting_save']);
        Route::post('fetch/dialer/setting', [DialerSettingController::class, 'dialer_setting']);
        Route::post('fetch/dialer/call-logs', [VoiceController::class, 'fetch_call_logs']);
        
    });
});

Route::post('/twilio-sms', [ChatController::class, 'handleIncomingMessage']);
Route::post('/dial-status', [CallController::class, 'handleDialStatus']);
Route::get('/stripe/top-up/checkout/success', [StripeController::class, 'topUpSuccess']);
Route::get('/stripe/top-up/checkout/cancel', [StripeController::class, 'topUpCancel']);
Route::post('/hold-call', [CallController::class, 'holdCall']);
Route::post('/resume-call', [CallController::class, 'resumeCall']);

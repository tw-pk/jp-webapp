<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TwilioVerifyService;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Twilio\Exceptions\TwilioException;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;
use Jenssegers\Agent\Facades\Agent;
use Illuminate\Support\Facades\Http;

class TwoFactorAuthController extends Controller
{
    private TwoFactorAuthenticationService $twoFactorAuthenticationService;
    public function __construct(TwoFactorAuthenticationService $twoFactorAuthenticationService)
    {
        $this->twoFactorAuthenticationService = $twoFactorAuthenticationService;
    }

    public function enable(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required',
            'channel' => 'required',
            ]);
    }

    public function generateOtp(Request $request)
    {
        $request->validate([
            'phoneNumber' => 'required',
            'channel' => 'required'
        ]);

        try {
            return $this->twoFactorAuthenticationService->generateCode($request->phoneNumber, $request->channel);
        } catch (TwilioException $exception) {
            return $exception->getMessage();
        }
    }

    public function disable()
    {
        $profile = \Auth::user()->twoFactorProfile;
        if (!$profile) {
            return response()->json([
                'status' => false,
                'message' => 'Two-Factor Authentication is not registered on your account, please enable first!'
            ]);
        }

        if ($profile->enabled) {
            $profile->enabled = false;
            $profile->save();

            return response()->json([
                'status' => true,
                'message' => 'Two-Factor Authentication is disabled successfully'
            ]);
        }
    }

    public function generateCode(Request $request){
        $request->validate([
            'phoneNumber' => 'required',
            'channel' => 'required'
        ]);

        $phoneNumber = $request->phoneNumber;
        $channel = $request->channel;

        $profile = \Auth::user()->twoFactorProfile;

        if($profile->phone !== $phoneNumber){
            return response()->json([
                'status' => false,
                'message' => 'Phone number does not match your current phone number',
                'errors' => [
                    'phoneNumber' => 'Phone number does not match your current phone number',
                ]
            ], 422);
        }

        if($profile->channel !== $channel){
            return response()->json([
                'status' => false,
                'message' => 'Sorry we cannot process your request at this time'
            ], 422);
        }

        try {
            return $this->twoFactorAuthenticationService->generateCode($phoneNumber, $channel);
        }catch (TwilioException $exception){
            return $exception->getMessage();
        }
    }

    public function resendCode(Request $request){
        return $this->twoFactorAuthenticationService->resendCode();
    }

    public function verifyOtp(Request $request){
        $request->validate([
            'to' => 'required',
            'code' => 'required|min:6|max:6'
        ]);

        try {
            $response = $this->twoFactorAuthenticationService->verifyCode(TwilioVerifyService::first()->sid, $request->to, $request->code);

            if (isset($response->original['status'])) {
                $status = $response->original['status'];
                if($status){
                    $request->session()->put('is_2fa_completed', true);
                }
            }

            return $response;

        }catch (TwilioException $exception){
            return $exception->getMessage();
        }
    }

    public function verifySession(Request $request): JsonResponse
    {
        return $this->twoFactorAuthenticationService->isSessionVerified($request);
    }

    public function twoFactorProfile()
    {
        $profile = \Auth::user()->twoFactorProfile;
        if ($profile) {
            // Get the length of the input string
            $length = strlen($profile->phone);

            // Extract the first three characters
            $firstThree = substr($profile->phone, 0, 7);


            // Create a string of asterisks ('*') with the same length as the original string
            $maskedPortion = str_repeat('*', $length - 7);

            // Concatenate the first seven characters and the masked portion
            $phone = $firstThree . $maskedPortion;
            return response()->json([
                'status' => true,
                'phoneNumber' => $phone,
                'channel' => $profile->channel
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Two factor authentication not enabled'
            ]);
        }
    }

    public function isEnabled()
    {
        $profile = Auth::user()->twoFactorProfile;
        
        $browser = Agent::browser();
        $browserVersion = Agent::version($browser);

        $deviceIconMap = [
            'Desktop' => ['icon' => 'tabler-brand-windows', 'color' => 'primary'],
            'Tablet' => ['icon' => 'tabler-device-tablet', 'color' => 'primary'],
            'Mobile' => ['icon' => 'tabler-brand-android', 'color' => 'primary'],
            'Smart TV' => ['icon' => 'tabler-device-tv', 'color' => 'primary'],
            'Smartwatch' => ['icon' => 'tabler-device-watch', 'color' => 'primary'],
        ];

        $device = 'Unknown';
        if (Agent::isDesktop()) {
            $device = 'Desktop';
        } elseif (Agent::isTablet()) {
            $device = 'Tablet';
        } elseif (Agent::isMobile()) {
            $device = 'Mobile';
        } elseif (Agent::isSmartTV()) {
            $device = 'Smart TV';
        } elseif (Agent::isSmartwatch()) {
            $device = 'Smartwatch';
        } 
        $deviceIcon = [];
        $deviceIcon = $deviceIconMap[$device] ?? [];
        
        $ip = request()->ip();
        $currentUserInfo = Location::get($ip);
        $location = $currentUserInfo ? $currentUserInfo->cityName.' '.$currentUserInfo->regionName.' '.$currentUserInfo->countryCode :'Unknown';

        $recentActivity = Auth::user()->last_login_at ? \Carbon\Carbon::parse(Auth::user()->last_login_at)->format('d M Y, H:i') : now()->format('d M Y, H:i');

        $recentDevices = [
            [
            'browser' => $browser . ' ' . $browserVersion,
            'device' => $device,
            'location' => $location,
            'recentActivity' => $recentActivity,
            'deviceIcon' => $deviceIcon,
            ],
        ];
        if (!$profile) {
            return response()->json([
                'status' => false,
                'message' => 'Two-Factor Authentication is not enabled',
                'recentDevices' => $recentDevices
            ]);
        }

        if ($profile->enabled) {
            return response()->json([
                'status' => true,
                'message' => 'Two-Factor Authentication is enabled on your account',
                'recentDevices' => $recentDevices
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Two-Factor Authentication is not enabled',
                'recentDevices' => $recentDevices
            ]);
        }
    }
}

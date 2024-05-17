<?php

namespace App\Services;

use App\Interfaces\TwoFactorAuthenticationInterface;
use App\Models\TwilioPasswordVerification;
use App\Models\TwilioVerifyService;
use App\Models\User;
use App\Models\TwoFactorProfiles;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwoFactorAuthenticationService implements TwoFactorAuthenticationInterface
{

    protected string $channel;
    protected int $codeExpiry;
    public int $code;
    private Client $twilio;

    public function __construct(Client $twilio)
    {
        $this->twilio = $twilio;
    }

    /**
     * @return string
     */
    public function getCodeExpiry(): string
    {
        return $this->codeExpiry;
    }

    /**
     * @param $time
     * @return void
     */
    public function setCodeExpiry($time): void
    {
        $this->codeExpiry = $time;
    }

    /**
     * @param string $phoneNumber
     * @param string $channel
     * @param int $expiryTime
     * @return JsonResponse
     */
    public function generateCode(string $phoneNumber, string $channel, int $expiryTime = 10): JsonResponse
    {
        try {
            $service = TwilioVerifyService::first();

            if (!$service) {
                $service = $this->twilio->verify->v2->services
                    ->create("JustDial OTP");
                TwilioVerifyService::create([
                    'sid' => $service->sid
                ]);
            }

            $user = \Auth::user();
            $profile = $user->twoFactorProfile;
            if (!$profile) {
                TwoFactorProfiles::create([
                    'user_id' => \Auth::user()->id,
                    'sid' => $service->sid,
                    'channel' => $channel,
                    'phone' => $phoneNumber,
                    'enabled' => false
                ]);
            }

            $verification = $this->twilio->verify->v2->services($service->sid)
                ->verifications
                ->create($phoneNumber, $channel);

            TwilioPasswordVerification::create([
                'user_id' => \Auth::user()->id,
                'sid' => $service->sid,
                'expiry_at' => Carbon::now()->addMinutes(10),
                'channel' => $verification->channel
            ]);
            
            $this->setCodeExpiry(10);

            return response()->json([
                'status' => true,
                'message' => 'OTP has been generated successfully'
            ]);
        } catch (TwilioException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function VerifyGenerateCode(array $data, int $expiryTime = 30): JsonResponse
    {
        try {
            $lastInsertedId = $data['lastInsertedId'];
            $phoneNumber = $data['phoneNumber'];
            $channel = $data['channel'];

            $service = TwilioVerifyService::first();

            if (!$service) {
                $service = $this->twilio->verify->v2->services
                    ->create("JustDial OTP");
                TwilioVerifyService::create([
                    'sid' => $service->sid
                ]);
            }

            $profile = TwoFactorProfiles::where('user_id', $lastInsertedId)->first();
            if (!$profile) {
                TwoFactorProfiles::create([
                    'user_id' => $lastInsertedId,
                    'sid' => $service->sid,
                    'channel' => $channel,
                    'phone' => $phoneNumber,
                    'enabled' => false
                ]);
            }

            $verification = $this->twilio->verify->v2->services($service->sid)
                ->verifications
                ->create($phoneNumber, $channel);

            TwilioPasswordVerification::create([
                'user_id' => $lastInsertedId,
                'sid' => $service->sid,
                'expiry_at' => Carbon::now()->addMinutes(30),
                'channel' => $verification->channel
            ]);
            
            $this->setCodeExpiry(30);

            return response()->json([
                'status' => true,
                'lastInsertedId' => $lastInsertedId,
                'message' => 'OTP has been generated successfully'
            ]);
        } catch (TwilioException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    public function resendCode(): JsonResponse
    {
        try {
            $service = TwilioVerifyService::first();
            $user = \Auth::user();
            $profile = $user->twoFactorProfile;
        
            if (!$service || !$profile) {
                return response()->json([
                    'status' => false,
                    'message' => 'Two-factor authentication is not enabled yet.'
                ]);
            }
        
            $verificationRecord = TwilioPasswordVerification::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();

            if (!$verificationRecord) {
                return response()->json([
                    'status' => false,
                    'message' => 'Verification record not found'
                ]);
            }
            
            $verification = $this->twilio->verify->v2->services($service->sid)
            ->verifications
            ->create($profile->phone, $profile->channel);    

            $verificationRecord->update(['expiry_at' => Carbon::now()->addMinutes(10)]);
        
            return response()->json([
                'status' => true,
                'message' => 'OTP resent successfully'
            ]);

        } catch (TwilioException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * @param string $serviceSid
     * @param string $to
     * @param int $code
     * @return JsonResponse
     * @throws TwilioException
     */
    public function verifyCode(string $serviceSid, string $to, int $code): JsonResponse
    {

        try {
            $verification_check = $this->twilio->verify->v2->services($serviceSid)
                ->verificationChecks
                ->create([
                    'to' => $to,
                    'code' => $code
                ]);

            $user = \Auth::user();
            $profile = $user->twoFactorProfile;

            if ($verification_check->status == 'approved') {
                $profile->enabled = true;
                $profile->save();
            }


            return response()->json([
                'status' => $verification_check->status === "approved",
                "message" => $verification_check->status === "approved" ? "Two factor authentication has been enabled on your account" : "Invalid OTP provided",
            ]);
        } catch (TwilioException $exception) {
            return response()->json([
                'status' => false,
                "message" => $exception->getMessage(),
            ]);
        }
    }

    public function registerVerifyCode(string $to, int $code, int $userId): JsonResponse
    {
        try {
            $serviceSid = TwilioVerifyService::first()->sid;
            $verification_check = $this->twilio->verify->v2->services($serviceSid)
                ->verificationChecks
                ->create([
                    'to' => $to,
                    'code' => $code
                ]);
            $profile = TwoFactorProfiles::where('user_id', $userId)->first();
            if ($verification_check->status == 'approved') {
                $profile->enabled = true;
                $profile->save();
            }
            
            if($verification_check->status){
                User::where('id', $userId)->update([
                    'phone_number_verified' => true
                ]);
            }
            return response()->json([
                'status' => $verification_check->status === "approved",
                "message" => $verification_check->status === "approved" ? "Phone number is verified" : "Invalid OTP Provided",
            ]);
        } catch (TwilioException $exception) {
            return response()->json([
                'status' => false,
                "message" => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function isSessionVerified(Request $request): JsonResponse
    {
        $profile = \Auth::user()->twoFactorProfile;
        if($profile){
            if($profile->enabled){
                if(!$request->session()->get('is_2fa_completed')){
                    return response()->json([
                        'status' => false,
                        'message' => 'User has not completed 2fa verification'
                    ]);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'User has completed 2fa verification'
                ]);
            }else{
                return response()->json([
                    'status' => true,
                    'message' => 'User is verified'
                ]);
            }
        }else{
            return response()->json([
                'status' => true,
                'message' => 'User is verified'
            ]);
        }
    }
}

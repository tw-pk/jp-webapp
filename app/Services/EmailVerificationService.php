<?php

namespace App\Services;

use App\Models\PasswordVerification;
use App\Notifications\EmailVerificationNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Http\JsonResponse;

class EmailVerificationService
{
    public \Illuminate\Contracts\Auth\Authenticatable|null|\App\Models\User $user;

    public function __construct()
    {
        $this->user = \Auth::user();
    }

    /**
     * @return true
     * @throws Exception
     */
    public function generateOtp($user=null): bool
    {
        $this->user = $user;

        $valid_otp_exists = $this->checkIfValidOtpAlreadyExists();

        if($valid_otp_exists){
            return false;
        }

        $otp = random_int(100000, 999999);
        $password_verification = new PasswordVerification();
        $password_verification->otp = $otp;
        $password_verification->user_id = $user->id;
        $password_verification->expires_at = Carbon::now()->addMinutes(3)->format('Y-m-d h:i'); 
        //Carbon::now()->addSeconds(30)->format('Y-m-d h:i');
        $password_verification->save();


        $user->notify(new EmailVerificationNotification('Use these 6-digits to verify your email', 'Account Verification', $user, $otp));

        return true;
    }

    /**
     * @throws Exception
     */
    public function resendOtp(): JsonResponse
    {

        $valid_otp_exists = $this->checkIfValidOtpAlreadyExists();

        if($valid_otp_exists){
            return response()->json([
                'message' => 'Please wait, a new OTP will be generated after 3 minutes'
            ]);
        }

        $otp = random_int(100000, 999999);
        $password_verification = new PasswordVerification();
        $password_verification->otp = $otp;
        $password_verification->user_id = $this->user->id;
        $password_verification->expires_at = Carbon::now()->addMinutes(3)->format('Y-m-d h:i');
        $password_verification->save();

        $this->user->notify(new EmailVerificationNotification('User these 6-digits to verify your email', 'Account Verification', $this->user, $otp));

        return response()->json([
            'message' => 'A new OTP has been sent to your email'
        ]);
    }

    public function verifyOtp($otp){
        return $this->checkIfOtpIsValid($otp);
    }

    public function checkIfValidOtpAlreadyExists(): bool
    {
        if($this->user->otps->count()){
            $latest_record = $this->user->otps->sortByDesc('created_at')->first();
            if($latest_record){
                if(($latest_record->expires_at > Carbon::now()->format('Y-m-d h:i')) && !$latest_record->verified){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    private function checkIfOtpIsValid($otp){

        if($this->user->otps->count()){
            $latest_record = $this->user->otps->sortByDesc('created_at')->first();
            if($latest_record) {
                if (($latest_record->expires_at > Carbon::now()->format('Y-m-d h:i'))) {
                    if($otp === strval($latest_record->otp)){
                        $latest_record->verified = true;
                        $latest_record->save();
                        $this->user->email_verified_at = Carbon::now();
                        $this->user->save();
                        return response()->json([
                            'status' => true,
                            'message' => 'OTP verified successfully.'
                        ]);
                    }else{
                        return response()->json([
                            'status' => false,
                            'message' => 'Invalid OTP provided!'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Your OTP has been expired please generate new OTP!'
                    ]);
                }
            }
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Please generate new OTP!'
            ]);
        }
    }

}

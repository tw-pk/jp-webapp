<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function verify(Request $request){
        $email_verification_service = new EmailVerificationService();
        return $email_verification_service->verifyOtp($request->otp);
    }

    /**
     * Get the authenticated User
     *
     * @param Request $request
     * @return JsonResponse [json] user object
     */
    public function resend(Request $request){
        $email_verification_service = new EmailVerificationService();
        return $email_verification_service->resendOtp();
    }

}

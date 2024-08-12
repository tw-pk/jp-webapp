<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\AutoTopUpPaymentService;

class CheckUserBalance
{
    protected $autoTopUpPaymentService;

    public function __construct(AutoTopUpPaymentService $autoTopUpPaymentService)
    {
        $this->autoTopUpPaymentService = $autoTopUpPaymentService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {        
        $userEmail = $request->email;
        $user = User::with('invitationsMember')->where('email', $userEmail)->first();
        $teamleadId;
        if ($user->hasRole('Admin')) {            
            $teamleadId = $user->id;
        } else {            
            $invitationMember = $user->invitationsMember;                    
            $teamleadId = $invitationMember->user_id;
        }

        $thresholdEnabled = UserCredit::where('user_id', $teamleadId)->first();
        $user = User::where('id', $teamleadId)->first();        

        if($thresholdEnabled->threshold_enabled == '1'){
            $this->autoTopUpPaymentService->checkAndTopUp($user);
        }

        return $next($request);
    }
}

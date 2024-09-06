<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Models\PasswordReset;
use App\Models\Plans;
use App\Models\UserProfile;
use App\Services\EmailVerificationService;
use App\Services\TwilioServices;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\CustomRole;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use App\Models\Invitation;
use Illuminate\Support\Facades\Log;
use Str;
use Symfony\Component\HttpKernel\Profiler\Profile;
use App\Services\TwoFactorAuthenticationService;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param Request $request
     * @return JsonResponse [string] message
     * @throws \Exception
     */

    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string',
            'c_password' => 'required|same:password',
            'privacyPolicies' => 'required|accepted',
            //'terms_agreement' => 'required|accepted',
        ]);
        
        $user = new User();
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->privacy_policy_agreed = true;
        
        if ($user->save()) {
            $invitationRole = Invitation::with('roleInfo')
                ->where('email', $user->email)
                ->first();

            if ($invitationRole) {
                $invitationRole->member_id = $user->id;
                $invitationRole->registered = true;
                $invitationRole->save();
                
            }

            $role = $invitationRole?->roleInfo;
            if (empty($role)) {
                $role = Role::where('name', 'Admin')->first();
            }
            $user->assignRole($role);

            $email_verification_service = new EmailVerificationService();
            $email_verification_service->generateOtp($user);

            return response()->json([
                'status' => true,
                //'lastInsertedId' => encrypt($user->id),
                'message' => 'Successfully created user!'
            ], 201);
        } else {
            return response()->json([
                'status' => false, 
                'error' => 'Provide proper details'
            ]);
        }
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'lastInsertedId' => 'required',
            'to' => 'required',
            'code' => 'required|min:6|max:6'
        ]);
        $lastInsertedId = decrypt($request->lastInsertedId);
        $twoFactorAuthenticationService = app(TwoFactorAuthenticationService::class);
        return $twoFactorAuthenticationService->registerVerifyCode($request->to, $request->code, $lastInsertedId);
    }

    public function verifyPhoneNumber(Request $request)
    {
        $request->validate([
            'lastInsertedId' => 'required',
            'phoneNumber' => 'required|numeric',
            'channel' => 'required|string'
        ]);
        $lastInsertedId = decrypt($request->lastInsertedId);
        $data = [
            'lastInsertedId' => $lastInsertedId,
            'phoneNumber' => $request->phoneNumber,
            'channel' => $request->channel
        ];
        $twoFactorAuthenticationService = app(TwoFactorAuthenticationService::class);
        return $twoFactorAuthenticationService->VerifyGenerateCode($data);
    }

    public function create_ten_dlc(Request $request)
    {
        $request->validate([
            'registerBusiness' => 'required|string',
            'friendlyName' => 'required|string',
            'businessName' => 'required|string',
            'addressLine1' => 'required|string',
            'city' => 'required|string',
            'regionState' => 'required|string',
            'country' => 'required|string',
            'zipcode' => 'required|string',
            'businessType' => 'required|string',
            'businessIndustry' => 'required|string',
            'businessRegistrationIdentifer' => 'required|string',
            'businessRegistrationNumber' => 'required|string',
            'businessIdentity' => 'required|string',
            'websiteLink' => 'required|string',
            'socialMediaProfileUrls' => 'required|string',
            'regionOfOperations' => 'required|string',
            'companyStatus' => 'required|string',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'jobPosition' => 'required|string',
            'email' => 'required|string|email',
            'phoneNumber' => 'required|string',
            'businessTitle' => 'required|string',
        ]);

        $user = $request->all();
        $twilioServices = new TwilioServices();
        $response = $twilioServices->createTwilioTenDLC($user);

        return response()->json($response, 201);
    }

    public function delete_ten_dlc(Request $request)
    {
        $request->validate([
            'sid' => 'required|string',
        ]);
        
        $user = $request->all();
        $twilioServices = new TwilioServices();
        $response = $twilioServices->deleteTwilioTenDLC($user);

        $message = 'Profile not deleted! There are some problem.';
        if($response ==true){
            $message = 'Successfully deleted profile 10DLC!';
        }
        return response()->json([
            'response' => $response,
            'message' => $message
        ], 201);
    }

    /**
     * Login user and create token
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'email' => ['Email or password invalid!'],
            ], 400);
        }

        $user = $request->user();

        $user->update([
            'last_login_at' => Carbon::now(),
        ]);

        $tokenResult = $user->createToken('Personal Access Token', ['*'], now()->addDays(3));
        $token = $tokenResult->plainTextToken;

        if (Auth::user()->hasRole(['Admin'])) {
            $userAbilities = [
                [
                    'action' => 'manage',
                    'subject' => 'all'
                ]
            ];
        } else {
            $userAbilities = [
                [
                    'action' => 'read',
                    'subject' => 'Member'
                ],
                [
                    'action' => 'read',
                    'subject' => 'dashboard-analytics'
                ],
                [
                    'action' => 'read',
                    'subject' => 'inbox'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Auth'
                ],
                [
                    'action' => 'read',
                    'subject' => 'teams'
                ],
                [
                    'action' => 'read',
                    'subject' => 'contact'
                ],
                [
                    'action' => 'read',
                    'subject' => 'contact-details'
                ],
                [
                    'action' => 'read',
                    'subject' => 'reports'
                ],
                [
                    'action' => 'read',
                    'subject' => 'phone-numbers'
                ],
                [
                    'action' => 'read',
                    'subject' => 'pages-account-settings-tab'
                ],
                [
                    'action' => 'read',
                    'subject' => 'account'
                ],
                [
                    'action' => 'read',
                    'subject' => 'security'
                ]
            ];
        }

        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            "lastname" => $user->lastname,
            'verified_at' => $user->email_verified_at,
            "role" => Auth::user()->getRoleNames()->first(),
            'avatar' => Auth::user()->profile ? (Auth::user()->profile->avatar != null ? asset('storage/avatars/' . Auth::user()->profile?->avatar) : null) : null,
            "bio" => $user->profile ? $user->profile->bio : "",
            'abilities' => $userAbilities
        ];

        return response()->json([
            'message' => 'Successfully created user!',
            'accessToken' => $token,
            'userData' => $userData,
            'token_type' => 'Bearer',
            'userAbilities' => $userAbilities
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {

        $user = ($query = User::query());

        $user = $user->where($query->qualifyColumn('email'), $request->input('email'))->first();
        
        if (!$user || !$user->email) {
            return response()->json([
                'status' => false,
                'message' => 'No record found, Incorrect email address provided',
            ], 404);
        }

        try {
            $token = Str::random(64);

            $previous_record = PasswordReset::where('email', $user->email)->delete();

            \DB::table('password_reset_tokens')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            $user->sendPasswordResetNotification($token);
        } catch (\Exception $e) {
            return new JsonResponse([$e->getMessage()]);
        }

        return response()->json([
            'status' => true,
            'message' => 'A code has been sent to your email address'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @param Request $request
     * @return JsonResponse [json] user object
     */
    public function user(Request $request)
    {
        $user = [
            'id' => $request->user()->id,
            'email' => $request->user()->email,
            'firstname' => $request->user()->firstname,
            'fullName' => $request->user()->fullName(),
            'lastname' => $request->user()->lastname,
            'email_verified' => $request->user()->email_verified_at ?? false,
            'numbers' => $request->user()->numbers->count(),
            'invitations' => $request->user()->invitations->count(),
            'can_have_new_number' => $request->user()?->invitationsMember?->can_have_new_number,
            "bio" => $request->user()->profile ? $request->user()->profile->bio : "",
            'avatar' => Auth::user()->profile ? (Auth::user()->profile->avatar != null ? asset('storage/avatars/' . Auth::user()->profile?->avatar) : null) : null
        ];
        return response()->json($user);
    }

    public function userProfileData()
    {
        if (!\auth()->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated User'
            ], 403);
        }

        $user = [
            'email' => Auth::user()->email,
            'firstName' => Auth::user()->firstname,
            'lastName' => Auth::user()->lastname,
            'phoneNumber' => Auth::user()->profile?->phone_number,
            'address' => Auth::user()->profile?->address,
            'state' => Auth::user()->profile?->state,
            'zipcode' => Auth::user()->profile?->zip_code,
            'country' => Auth::user()->profile?->country,
            'timezone' => Auth::user()->profile?->timezone,
            'organization' => Auth::user()->profile?->organization,
            'bio' => Auth::user()->profile?->bio,
            'avatar' => Auth::user()->profile ? (Auth::user()->profile->avatar != null ? asset('storage/avatars/' . Auth::user()->profile?->avatar) : null) : null
        ];

        return response()->json($user);
    }

    public function accountDeactivate()
    {
        if (!\auth()->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated User'
            ], 403);
        }

        $user = Auth::user();
        $user->delete(); // Soft delete the user

        return response()->json([
            'status' => true,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'firstName' => 'required',
            'lastName' => 'required',
            'phoneNumber' => 'required',
            'address' => 'required',
            'state' => 'required',
            'country' => 'required',
            'zipcode' => 'required',
            'timezone' => 'required',
            'organization' => 'required',
            'avatar' => 'sometimes'
        ]);

        $user = Auth::user();
        $user->firstname = $request->firstName;
        $user->lastname = $request->lastName;
        $user->save();

        $profile = Auth::user()->profile;
        
        if (!$profile) {
            $profile = new UserProfile();
            $profile->user_id = Auth::user()->id;
            $this->updatedUserProfile($request, $profile);
        } else {
            $this->updatedUserProfile($request, $profile);
        }
        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'password_confirmation' => 'required|same:password',
            'email' => 'required|email'
        ]);

        $check_password = \DB::table('password_reset_tokens')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$check_password) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid token!'
            ], 400);
        }

        User::where('email', $request->email)->update([
            'password' => bcrypt($request->password)
        ]);

        \DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    public function verifyResetLink(Request $request)
    {

        $tokenData = PasswordReset::where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            // Token not found, handle the error
            return response()->json(['error' => 'Invalid password reset token'], 400);
        }

        $token_created_at = Carbon::parse($tokenData->created_at)->addMinutes(60)->format('Y-m-d h:i a');
        $date_now = Carbon::now()->addMinutes(60)->format('Y-m-d h:i a');

        if ($token_created_at > $date_now) {
            // Token has expired, handle the error
            return response()->json(['error' => 'Password reset token has expired'], 400);
        }

        return response()->json([
            'message' => 'Link is valid'
        ]);
    }

    /**
     * @param Request $request
     * @param $profile
     * @return void
     */
    public function updatedUserProfile(Request $request, $profile): void
    {
        $profile->state = $request->state;
        $profile->address = $request->address;
        $profile->country = $request->country;
        $profile->timezone = $request->timezone;
        $profile->zip_code = $request->zipcode;
        $profile->organization = $request->organization;
        $profile->phone_number = $request->phoneNumber;
        $profile->bio = $request->bio;
        $profile->save();
        
        if (!empty($request->avatar) && $request->has('avatar')) {
            // Get filename with the extension
            $filenameWithExt = $request->file('avatar')->getClientOriginalName();
            //Get just filename
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            // Get just ext
            $extension = $request->file('avatar')->getClientOriginalExtension();
            // Filename to store
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            // Upload Image
            $path = $request->file('avatar')->storeAs('public/avatars', $fileNameToStore);

            $profile->avatar = $fileNameToStore;

            $profile->save();
        }
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:8',
            'confirmPassword' => 'required|same:newPassword'
        ]);

        if (!\Hash::check($request->currentPassword, Auth::user()->password)) {
            return response()->json([
                'status' => false,
                'errors' => [
                    'currentPassword' => 'Invalid current password, does not match your actual password!'
                ]
            ], 422);
        }

        $user = Auth::user();
        $user->password = \Hash::make($request->newPassword);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    public function fetch_total()
    {

        if (!auth()->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Session Expired'
            ], 413);
        }

        $totalNumbers = Auth::user()->numbers->count();
        $subtotal = Plans::where('name', 'Basic')->first()->price * $totalNumbers;
        $isUserSubscribed = Auth::user()->subscription('Basic');

        return response()->json([
            'status' => true,
            'message' => $isUserSubscribed ? 'You are currently subscribed on Basic Plans' : 'You have not subscribed to any plan, yet',
            'totalNumbers' => $totalNumbers,
            'subtotal' => '$' . number_format($subtotal, 2),
            'pricePerNumber' => '$' . number_format(Plans::where('name', 'Basic')->first()->price, 2)
        ]);
    }

    public function isRole()
    {
        $roleName = Auth::user()->getRoleNames()->first();
        if(!$roleName){
            return response()->json([
                'status' => false,
                'message' => 'Roll has been not exist.'
            ]);  
        }
        return response()->json([
            'status' => true,
            'is'.$roleName => Auth::user()->hasRole([$roleName])
        ]);
    }

}

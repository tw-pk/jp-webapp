<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Contact;
use App\Models\UserNumber;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\CallList;
use Twilio\Rest\Client;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use App\Services\StatusService;
use App\Services\AssignPhoneNumberService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class VoiceController extends Controller
{
    public Client $twilio;
    protected string $role;

    /**
     * @throws ConfigurationException
     */
    public function __construct()
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $this->twilio = new Client($sid, $token);
        $this->assignPhoneNumberService = new AssignPhoneNumberService();
    }

    private function isUserAdmin()
    {
        $this->role = Auth::user()->getRoleNames()->first();
        return $this->role === 'Admin';
    }

    /**
     * @throws TwilioException
     */
    public function call(Request $request)
    {
        $request->validate([
            'to'
        ]);

        if (!Auth::user()->contacts->count()) {
            return response()->json([
                'status' => false,
                'message' => 'No contacts exist'
            ]);
        }

        $call = $this->twilio->calls
            ->create($request->to, Auth::user()->numbers->where('active', true)->first()->phone_number, [
                "twiml" => "<Response><Say>Hello sunny this is a test call from Hamaad, just testing voice API</Say></Response>"
            ]);

        $call_created = Call::create([
            'user_id' => Auth::user()->id,
            'contact_id' => Auth::user()->contacts->where('phone', $request->to)->first()->id,
            'sid' => $call->sid,
            'to' => $request->to,
            'from' => Auth::user()->numbers->where('active', true)->first()->phone_number,
            'status' => $call->status ?? "-",
            'duration' => $call->duration ? $call->duration . " seconds" : '-',
            'direction' => $call->direction ?? "-",
            'date_time' => isset($call->startTime, $call->endTime) 
                ? "From " . Carbon::parse($call->startTime)->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A') . " - To " . Carbon::parse($call->endTime)->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A')
                : "From " . Carbon::now()->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A') . " - To " . Carbon::now()->setTimezone('Asia/Karachi')->format('d M, Y h:i:s A'),

        ]);

        Log::info('inside VoiceController');
        
        if ($call_created) {
            return response()->json([
                'status' => true,
                'message' => 'Voice call initiated'
            ]);
        }
    }

    public function call_test()
    {
        $requestTo = '+918448402940';

        $call = $this->twilio->calls
            ->create($requestTo, Auth::user()->numbers->first()->phone_number, [
                "twiml" => "<Response><Say>Call ended. Goodbye I Love You</Say></Response>"
            ]);

        dd($call);
    }

    public function generateDialXml()
    {
        $fromNumber = '+15418735198';
        $toNumber = '+918448402940';

        $xmlContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xmlContent .= "<Response>\n";
        $xmlContent .= "    <Dial callerId=\"$fromNumber\">\n";
        $xmlContent .= "        $toNumber\n";
        $xmlContent .= "    </Dial>\n";
        $xmlContent .= "    <Say>Call ended. Goodbye</Say>\n";
        $xmlContent .= "</Response>";

        return response($xmlContent)->header('Content-Type', 'application/xml');
    }

    public function recent_calls(Request $request)
    {
        $selectedItem = $request->input('selectedItem');
        $callTrait = $request->input('callTrait');
        $dateRange = $request->input('dateRange');
        $member = $request->input('member');
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        $user = Auth::user();

        if (!empty($member) && $member !="all") {
            $user = User::find($member);
        }

        $assignPhoneNumbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($user->id);
        if (count($assignPhoneNumbers) === 0) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any active number, please verify if you have an active number",
            ], 404);
        }

        try {
            $perPage = $options['itemsPerPage'];
            $currentPage = $options['page'] ?? 1;
            $filter = [];

            if (!empty($callTrait) && is_array($callTrait)) {
                $filter['status'] = $callTrait;
            }

            if (!empty($dateRange) && is_string($dateRange)) {
                $dateArray = explode('to', $dateRange);
                if (!empty($dateArray[0])) {
                    $startTimeBefore = $dateArray[0];
                    $filter['startTimeBefore'] = Carbon::parse($startTimeBefore)->format('Y-m-d H:i:s');
                }
                if (!empty($dateArray[1])) {
                    $startTimeAfter = $dateArray[1];
                    $filter['startTimeAfter'] = Carbon::parse($startTimeAfter)->format('Y-m-d H:i:s');
                }
            }
            
            $twilioCalls = Call::where(function ($query) use ($assignPhoneNumbers) {
                    $query->whereIn('to', $assignPhoneNumbers)
                        ->orWhereIn('from', $assignPhoneNumbers);
                })
                ->when($searchQuery, function ($query, $searchQuery) {
                    $query->where('to', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('from', 'LIKE', "%{$searchQuery}%");
                })
                ->when($selectedItem !== 'Default', function ($query) use ($selectedItem) {
                    $query->where('direction', 'LIKE', "%{$selectedItem}%");
                })
                ->when($filter, function ($query) use ($filter) {
                    // Apply filters if provided
                    if (!empty($filter['startTimeBefore']) && empty($filter['startTimeAfter'])) {
                        $query->where('created_at', '<=', $filter['startTimeBefore']);
                    }

                    if (!empty($filter['startTimeBefore']) && !empty($filter['startTimeAfter'])) {
                        $query->whereBetween('created_at', [$filter['startTimeBefore'], $filter['startTimeAfter']]);
                    }

                    if (!empty($filter['status'])) {
                        $query->whereIn('status', (array) $filter['status']);
                    }
                })
                ->orderByDesc('created_at')
                ->get();
            
            $twilioCalls = $this->formattedDateTime($twilioCalls);
            $totalRecord = $twilioCalls->count(); 
            $totalPage = ceil($totalRecord / $perPage);

            $allCalls = [];
            foreach ($twilioCalls as $call) {
                //$recordings = $this->twilio->recordings->read(["callSid" => $call->sid]);
                //$recordingUrl = count($recordings) > 0 ? $recordings[0]->uri : '-';
                $recordingPath = 'voicemail/' . $call->sid . '.mp3';
                $filePath = storage_path('app/public/' . $recordingPath);
                $recordingUrl = file_exists($filePath) ? asset('storage/' . $recordingPath) : '-';

                if($call->direction == 'inbound'){
                    $teamdialerNumber = $call->to;
                    $number = $call->from;
                }else{
                    $teamdialerNumber = $call->from;
                    $number = $call->to;
                }
                $allCalls[] = [
                    'call_id' => $call->id,
                    'call_sid' => $call->sid,
                    'teamdialer_number' => $teamdialerNumber,
                    'number' => $number,
                    'status' => $call->status ?? '-',
                    'direction' => $call->direction,
                    'date' => Carbon::parse($call->created_at)->setTimezone('Asia/Karachi')->diffForHumans(),
                    'duration' => $call->duration ?? '-',
                    'notes' => '',
                    'record' => $recordingUrl
                ];
            }

            $startIndex = ($currentPage - 1) * $perPage;
            $slicedCalls = array_slice($allCalls, $startIndex, $perPage);
            return response()->json([
                'status' => true,
                'calls' => $slicedCalls,
                'totalPage' => $totalPage,
                'totalRecord' => $totalRecord,
                'page' => $currentPage,
            ]);
            
        } catch (TwilioException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function recent_calls_contact(Request $request)
    {
        $callType = $request->input('callType');
        $searchQuery = $request->input('q');
        $options = $request->input('options');
        
        $user = Auth::user();
        $assignPhoneNumbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($user->id);
        if (count($assignPhoneNumbers) === 0) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any active number, please verify if you have an active number",
            ], 404);
        }

        try {
            $perPage = $options['itemsPerPage'];
            $currentPage = $options['page'] ?? 1;
            $filter = [];

            $twilioCalls = Call::where(function ($query) use ($assignPhoneNumbers) {
                    $query->whereIn('to', $assignPhoneNumbers)
                        ->orWhereIn('from', $assignPhoneNumbers);
                })
                ->when($searchQuery, function ($query, $searchQuery) {
                    $query->where('to', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('from', 'LIKE', "%{$searchQuery}%");
                })
                ->when($callType && $callType !== 'all', function ($query) use ($callType) {
                    $this->getCallTypeCriteria($query, $callType);
                })
                ->orderByDesc('created_at')
                ->get();
            
            $twilioCalls = $this->formattedDateTime($twilioCalls);
            $totalRecord = $twilioCalls->count(); 
            $totalPage = ceil($totalRecord / $perPage);

            $allCalls = [];
            foreach ($twilioCalls as $call) {
                if($call->direction == 'inbound'){
                    $teamdialerNumber = $call->to;
                    $number = $call->from;
                }else{
                    $teamdialerNumber = $call->from;
                    $number = $call->to;
                }
                $allCalls[] = [
                    'call_id' => $call->id,
                    'call_sid' => $call->sid,
                    'teamdialer_number' => $teamdialerNumber,
                    'number' => $number,
                    'status' => $call->status ?? '-',
                    'direction' => $call->direction,
                    'date' => Carbon::parse($call->created_at)->setTimezone('Asia/Karachi')->diffForHumans(),
                    'duration' => $call->duration ?? '-',
                ];
            }

            $startIndex = ($currentPage - 1) * $perPage;
            $slicedCalls = array_slice($allCalls, $startIndex, $perPage);
            return response()->json([
                'status' => true,
                'calls' => $slicedCalls,
                'totalPage' => $totalPage,
                'totalRecord' => $totalRecord,
                'page' => $currentPage,
            ]);

        } catch (TwilioException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getTeamsDialerNumber($call)
    {
        if ($call->direction === 'inbound') {
            return $call->to;
        } else {
            return $call->from;
        }
    }

    private function getNumber($call)
    {
        if ($call->direction === 'inbound') {
            return $call->from;
        } else {
            return $call->to;
        }
    }

    public function recent_calls_dash(Request $request)
    {
        $callType = $request->input('callType');
        $member = $request->input('member');
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        $user = Auth::user();

        if (!empty($member) && $member !="all") {
            $user = User::find($member);
        }

        $assignPhoneNumbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($user->id);
        if (count($assignPhoneNumbers) === 0) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any active number, please verify if you have an active number",
            ], 404);
        }

        try {
            $perPage = $options['itemsPerPage'];
            $currentPage = $options['page'] ?? 1;
            $filter = [];

            $twilioCalls = Call::where(function ($query) use ($assignPhoneNumbers) {
                    $query->whereIn('to', $assignPhoneNumbers)
                        ->orWhereIn('from', $assignPhoneNumbers);
                })
                ->when($searchQuery, function ($query, $searchQuery) {
                    $query->where('to', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('from', 'LIKE', "%{$searchQuery}%");
                })
                ->when($callType && $callType !== 'all', function ($query) use ($callType) {
                    $this->getCallTypeCriteria($query, $callType);
                })
                ->orderByDesc('created_at')
                ->get();
            
            $twilioCalls = $this->formattedDateTime($twilioCalls);
            $totalRecord = $twilioCalls->count(); 
            $totalPage = ceil($totalRecord / $perPage);

            $allCalls = [];
            foreach ($twilioCalls as $call) {
                //$recordings = $this->twilio->recordings->read(["callSid" => $call->sid]);
                //$recordingUrl = count($recordings) > 0 ? $recordings[0]->uri : '-';
                $recordingPath = 'voicemail/' . $call->sid . '.mp3';
                $filePath = storage_path('app/public/' . $recordingPath);
                $recordingUrl = file_exists($filePath) ? asset('storage/' . $recordingPath) : '-';

                if($call->direction == 'inbound'){
                    $teamdialerNumber = $call->to;
                    $number = $call->from;
                }else{
                    $teamdialerNumber = $call->from;
                    $number = $call->to;
                }
                $allCalls[] = [
                    'call_id' => $call->id,
                    'call_sid' => $call->sid,
                    'teamdialer_number' => $teamdialerNumber,
                    'number' => $number,
                    'status' => $call->status ?? '-',
                    'direction' => $call->direction,
                    'date' => Carbon::parse($call->created_at)->setTimezone('Asia/Karachi')->diffForHumans(),
                    'duration' => $call->duration ?? '-',
                    'notes' => '',
                    'record' => $recordingUrl
                ];
            }

            $startIndex = ($currentPage - 1) * $perPage;
            $slicedCalls = array_slice($allCalls, $startIndex, $perPage);
            return response()->json([
                'status' => true,
                'calls' => $slicedCalls,
                'totalPage' => $totalPage,
                'totalRecord' => $totalRecord,
                'page' => $currentPage,
            ]);

        } catch (TwilioException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function dashNumberAnalysis(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        try {

            $perPage = $options['itemsPerPage'] ?? 10;
            $currentPage = $options['page'] ?? 1;

            $userId = Auth::user()->id;
            $numbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($userId);
            if (empty($numbers)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No phone numbers assigned to the user.',
                ], 404);
            }

            $userNumbers = UserNumber::select('user_id', 'phone_number', 'country_code', 'country')
                ->whereIn('phone_number', $numbers);  
            
            if ($searchQuery) {
                $userNumbers->where('phone_number', 'like', '%' . $searchQuery . '%');
            }
            
            $userNumbers = $userNumbers->get(); 
            $totalRecord = $userNumbers->count();
            $totalPage = ceil($totalRecord / $perPage);
            $allNumbers = [];
        
            foreach ($userNumbers as $number) {
                $phoneNumber = $number->phone_number;
                $callRecords = Call::selectRaw("
                    SUM(CASE WHEN direction = 'outbound-dial' OR direction = 'outbound-api' THEN 1 ELSE 0 END) AS outbound,
                    SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) AS inbound,
                    SUM(CASE WHEN status = 'no-answer' THEN 1 ELSE 0 END) AS missed
                ")->where(function($query) use ($phoneNumber) {
                    $query->where('to', $phoneNumber)
                          ->orWhere('from', $phoneNumber);
                })
                ->first();

                $friendlyName = $this->formatPhoneNumber($phoneNumber);
                $flagUrl = $this->getCountryFlagUrl($number->country_code);

                $allNumbers[] = [
                    'number' => $phoneNumber,
                    'friendly_name' => $friendlyName,
                    'flag_url' => $flagUrl,
                    'outbound' => $callRecords->outbound ?? 0,
                    'inbound' => $callRecords->inbound ?? 0,
                    'missed' => $callRecords->missed ?? 0,
                ];
            }
            
            $startIndex = ($currentPage - 1) * $perPage;
            $slicedNumbers = array_slice($allNumbers, $startIndex, $perPage);
            return response()->json([
                'status' => true,
                "numbers" => $slicedNumbers,
                'totalPage' => $totalPage,
                'totalRecord' => $totalRecord,
                'page' => $currentPage,
            ]);
        } catch (TwilioException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function dashMemberList(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        try {
            $perPage = $options['itemsPerPage'] ?? 10;
            $currentPage = $options['page'] ?? 1;

            $userId = Auth::user()->id;

            $teamMembersQuery = Auth::user()->invitations()
                ->where('registered', 1)
                ->with(['invitationAccept' => function ($query) use ($searchQuery) {
                    if (!empty($searchQuery)) {
                        $query->where(function ($query) use ($searchQuery) {
                            $query->where('firstname', 'LIKE', '%' . $searchQuery . '%')
                                ->orWhere('lastname', 'LIKE', '%' . $searchQuery . '%');
                        });
                    }
                    $query->select('id', 'firstname', 'lastname', 'last_login_at');
                }])
                ->orderBy('created_at', 'desc');

            $teamMembers = $teamMembersQuery->get()->map(function ($invitation) {
                return $invitation->invitationAccept;
            });
            
            $teamMembers = $teamMembers->filter(function ($teamMember) {
                return $teamMember !== null;
            });
        
            $totalRecord = $teamMembers->count();
            $totalPage = ceil($totalRecord / $perPage);
        
                foreach ($teamMembers as $member) {
                    $member->fullName = $member->fullName();
                    $member->avatar = $member?->profile?->avatar ? asset('storage/avatars/' . $member->profile->avatar) : null;
                    
                    $curtime = Carbon::now();
                    $dateTime = $member->last_login_at ? Carbon::parse($member->last_login_at) : $curtime;
                    $formattedDate = "Since " . $dateTime->format('j M, \a\t h:i A');
                    $member->last_login_at = $formattedDate;
                    
                    $member->status = StatusService::getUserStatus($member->id);

                    $numbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($member->id);
                    if (!empty($numbers)) {
                        $callRecords = Call::selectRaw("
                            SUM(CASE WHEN direction = 'outbound-dial' OR direction = 'outbound-api' THEN 1 ELSE 0 END) AS outboundCalls,
                            SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) AS inboundCalls
                        ")->where(function($query) use ($numbers) {
                            $query->whereIn('to', $numbers)
                                  ->orWhereIn('from', $numbers);
                        })->first();

                        $member->outboundCalls = $callRecords->outboundCalls ?? 0;
                        $member->inboundCalls = $callRecords->inboundCalls ?? 0;
                    } else {
                        $member->outboundCalls = 0;
                        $member->inboundCalls = 0;
                    }
                }
                
                $slicedNumbers = $teamMembers->slice(($currentPage - 1) * $perPage, $perPage)->values();
                return response()->json([
                    'status' => true,
                    "teamMembers" => $slicedNumbers,
                    'totalPage' => $totalPage,
                    'totalRecord' => $totalRecord,
                    'page' => $currentPage,
                ]);
        } catch (TwilioException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dashLiveCalls()
    {
        $totalOutboundCalls = 0;
        $totalInboundCalls = 0;
        $totalCompletedCalls = 0;
        $totalLiveCalls = 0;
        $totalMissed = 0;

        $userId = Auth::user()->id;
        $numbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($userId);

        if (!empty($numbers)) {
            $callRecords = Call::selectRaw("
                SUM(CASE WHEN direction IN ('outbound-dial', 'outbound-api') THEN 1 ELSE 0 END) AS outboundCalls,
                SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) AS inboundCalls,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completedCalls,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) AS liveCalls,
                SUM(CASE WHEN status = 'no-answer' THEN 1 ELSE 0 END) AS missedCalls
            ")
            ->where(function ($query) use ($numbers) {
                $query->whereIn('to', $numbers)
                      ->orWhereIn('from', $numbers);
            })
            ->first();
        
            $totalOutboundCalls = intval($callRecords->outboundCalls ?? 0);
            $totalInboundCalls = intval($callRecords->inboundCalls ?? 0);
            $totalCompletedCalls = intval($callRecords->completedCalls ?? 0);
            $totalLiveCalls = intval($callRecords->liveCalls ?? 0);
            $totalMissed = intval($callRecords->missedCalls ?? 0);
        }

        return response()->json([
            'totalOutboundCalls' => $totalOutboundCalls,
            'totalInboundCalls' => $totalInboundCalls,
            'totalCompletedCalls' => $totalCompletedCalls,
            'totalLiveCalls' => $totalLiveCalls,
            'totalMissed' => $totalMissed,
        ]);
    }

    protected function getCallTypeCriteria($query=null, $callType=null)
    {
        switch ($callType) {
            case 'outbound-dial':
                return $query->where(
                    fn ($q) => $q->where('direction', 'outbound-api')
                        ->orWhere('direction', 'outbound-dial')
                );
            case 'inbound':
                return $query->where('direction', 'inbound');
            case 'missed':
                return $query->where('status', 'no-answer');
            case 'voicemail':
                $query->whereIn('status', ['no-answer','busy','in-progress','ringing']);
            default:
                return $query;
        }
    }

    public function formatPhoneNumber($phoneNumber=null)
    {
        if (empty($phoneNumber)) {
            return 'Error: Phone number is required';
        }

        $cleaned = preg_replace('/\D/', '', $phoneNumber);
        if (preg_match('/^1?(\d{3})(\d{3})(\d{4})$/', $cleaned, $matches)) {
            return '(' . $matches[1] . ') ' . $matches[2] . '-' . $matches[3];
        }
        return $phoneNumber;
    }

    private function getCountryFlagUrl($countryCode=null)
    {
        if ($countryCode) {
            $code = Str::lower($countryCode);
            return asset('images/flags/' . $code . '.png');
        }
        return '';
    }

    public function fetch_call_logs(Request $request)
    {
        $recent = NULL;
        $missed = NULL;
        $voicemail = NULL;
        $user_id = Auth::user()->id;
        $searchQuery = $request->input('q');

        if ($request->current_tab == 'tab-phone') {
            $recentCalls = Call::select('to', 'date_time')->where('user_id', $user_id)
                ->where('status', '!=', 'no-answer')
                ->where('status', '!=', 'voice-mail')
                ->whereIn('direction', ['outbound-api', 'outbound-dial', 'inbound'])
                ->when($searchQuery, function ($query, $searchQuery) {
                    return $query->where('to', 'LIKE', "%{$searchQuery}%");
                })
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
            $recent = $this->formattedDateTime($recentCalls);
        } else if ($request->current_tab == 'tab-missed') {
            $missedCalls = Call::select('from', 'date_time')->where('user_id', $user_id)
                ->where('status', 'no-answer')
                ->when($searchQuery, function ($query, $searchQuery) {
                    return $query->where('from', 'LIKE', "%{$searchQuery}%");
                })
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
            $missed = $this->formattedDateTime($missedCalls);
        } else if ($request->current_tab == 'tab-voicemail') {
            $voicemailCalls = Call::select('to', 'date_time')->where('user_id', $user_id)
                ->where('status', 'voice-mail')
                ->when($searchQuery, function ($query, $searchQuery) {
                    return $query->where('to', 'LIKE', "%{$searchQuery}%");
                })
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
            $voicemail = $this->formattedDateTime($voicemailCalls);
        }

        return response()->json([
            'status' => true,
            "recent" => $recent,
            "missed" => $missed,
            "voicemail" => $voicemail,
        ]);
    }

    private function formattedDateTime($calls = null)
    {
        $calls->map(function ($call) {
            $dateTimeString = $call->date_time;
            preg_match('/From (.+?) - To (.+?)$/', $dateTimeString, $matches);
            if (!$matches) {
                return null;
            }
            $fromDateTime = \Carbon\Carbon::createFromFormat('d M, Y h:i:s A', $matches[1]);
            $toDateTime = \Carbon\Carbon::createFromFormat('d M, Y h:i:s A', $matches[2]);
            $formattedDateTime = $fromDateTime->isToday()
                ? 'Today, ' . $fromDateTime->format('g:i A')
                : $fromDateTime->format('M j, Y, g:i A');
            $duration = $toDateTime->diff($fromDateTime)->format('%im %Ss');
            $call->formatted_date_time = $formattedDateTime;
            $call->duration = $duration;
        });
        return $calls;
    }
}

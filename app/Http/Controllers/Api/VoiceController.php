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
use App\Services\AssignPhoneNumberService;
use Illuminate\Support\Str;

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
            'from' => Auth::user()->numbers->where('active', true)->first()->phone_number
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

        if (!empty($member)) {
            $user = User::find($member);
        }

        $assignPhoneNumberService = new AssignPhoneNumberService();
        $assignPhoneNumbers = $assignPhoneNumberService->getAssignPhoneNumbers($user->id);
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
                    $query->whereIn('to', $assignPhoneNumbers);
                        //->orWhereIn('from', $assignPhoneNumbers);
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
                $recordingUrl = $call->sid ? asset('storage/voicemail/' . $call->sid) : '-';

                if($call->direction == 'inbound'){
                    $teamdialerNumber = $call->to;
                    $number = $call->from;
                }else{
                    $teamdialerNumber = $call->from;
                    $number = $call->to;
                }
                $allCalls[] = [
                    'call_sid' => $call->sid,
                    'teamdialer_number' => $teamdialerNumber,
                    'number' => $number,
                    'status' => $call->status ?? '-',
                    'direction' => $call->direction,
                    'date' => Carbon::parse($call->created_at)->setTimezone('Asia/Karachi')->diffForHumans(),
                    'duration' => $call->duration ?? '-',
                    'notes' => '',
                    'rating' => '-',
                    'disposition' => '-',
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

        $perPage = $options['itemsPerPage'];
        $currentPage = $options['page'] ?? 1;

        if ($this->isUserAdmin()) {

            if (!Auth::user()->numbers->count()) {
                return response()->json([
                    'status' => false,
                    'message' => "You don't have any active number, please verify if you have an active number",
                ], 404);
            }

            try {

                if (!empty($searchQuery)) {
                    $twilioCalls = Auth::user()->calls()
                        ->where('to', 'LIKE', '%' . $searchQuery . '%')
                        ->paginate($perPage, ['*'], 'page', $currentPage);
                } else {
                    if ($callType !== 'all') {
                        $twilioCalls = Auth::user()->calls()
                            ->where(fn ($q) => $this->getCallTypeCriteria($q, $callType))
                            ->paginate($perPage, ['*'], 'page', $currentPage);
                    } else {
                        $twilioCalls = Auth::user()->calls()
                            ->paginate($perPage, ['*'], 'page', $currentPage);
                    }
                }

                $totalRecord = $twilioCalls->total();
                $totalPage = ceil($totalRecord / $perPage);
                $allCalls = [];
                foreach ($twilioCalls as $call) {
                    if (!Str::startsWith($call->from, 'client') && !Str::startsWith($call->to, 'client')) {
                        $allCalls[] = [
                            'call_sid' => $call->sid,
                            'teamdialer_number' => $this->getTeamsDialerNumber($call),
                            'number' => $this->getNumber($call),
                            'status' => $call->status ?? '-',
                            'direction' => $call->direction,
                            'date' => $call->date_time,
                            'duration' => $call->duration . " seconds" ?? '-',
                            'notes' => '',
                            'rating' => '-',
                            'disposition' => '-',
                            'record' => '-'
                        ];
                    }
                }
                return response()->json([
                    'status' => true,
                    "calls" => $allCalls,
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
        } else {
            try {
                $from = Invitation::where('member_id', Auth::user()->id)->first()?->number;
                
                if (!empty($searchQuery)) {
                    $twilioCalls = Auth::user()->calls()
                        ->where('to', 'LIKE', '%' . $searchQuery . '%')
                        ->where('from', $from)
                        ->paginate($perPage, ['*'], 'page', $currentPage);
                } else {
                    if ($callType !== 'all') {
                        $twilioCalls = Auth::user()->calls()
                            ->where(fn ($q) => $this->getCallTypeCriteria($q, $callType))
                            ->where('from', $from)
                            ->paginate($perPage, ['*'], 'page', $currentPage);
                    } else {
                        $twilioCalls = Auth::user()->calls()
                            ->where('from', $from)
                            ->paginate($perPage, ['*'], 'page', $currentPage);
                    }
                }

                $totalRecord = $twilioCalls->total();
                $totalPage = ceil($totalRecord / $perPage);
                $allCalls = [];
                foreach ($twilioCalls as $call) {
                    if (!Str::startsWith($call->from, 'client') && !Str::startsWith($call->to, 'client')) {
                        $allCalls[] = [
                            'call_sid' => $call->sid,
                            'teamdialer_number' => $this->getTeamsDialerNumber($call),
                            'number' => $this->getNumber($call),
                            'status' => $call->status ?? '-',
                            'direction' => $call->direction,
                            'date' => $call->date_time,
                            'duration' => $call->duration . " seconds" ?? '-',
                            'notes' => '',
                            'rating' => '-',
                            'disposition' => '-',
                            'record' => '-'
                        ];
                    }
                }
                return response()->json([
                    'status' => true,
                    "calls" => $allCalls,
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

        if (!empty($member)) {
            $user = User::find($member);
        }

        $assignPhoneNumberService = new AssignPhoneNumberService();
        $assignPhoneNumbers = $assignPhoneNumberService->getAssignPhoneNumbers($user->id);
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
                    $query->whereIn('to', $assignPhoneNumbers);
                        //->orWhereIn('from', $assignPhoneNumbers)
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
                $recordingUrl = $call->sid ? asset('storage/voicemail/' . $call->sid) : '-';

                if($call->direction == 'inbound'){
                    $teamdialerNumber = $call->to;
                    $number = $call->from;
                }else{
                    $teamdialerNumber = $call->from;
                    $number = $call->to;
                }
                $allCalls[] = [
                    'call_sid' => $call->sid,
                    'teamdialer_number' => $teamdialerNumber,
                    'number' => $number,
                    'status' => $call->status ?? '-',
                    'direction' => $call->direction,
                    'date' => Carbon::parse($call->created_at)->setTimezone('Asia/Karachi')->diffForHumans(),
                    'duration' => $call->duration ?? '-',
                    'notes' => '',
                    'rating' => '-',
                    'disposition' => '-',
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
            $assignPhoneNumberService = new AssignPhoneNumberService();
            $numbers = $assignPhoneNumberService->getAssignPhoneNumbers($userId);
            
            if (empty($numbers )) {
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
                ")->where('to', $phoneNumber)->first();

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


    public function dash_member_list(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');
        try {
            $perPage = $options['itemsPerPage'];

            $currentPage = $options['page'] ?? 1;
            $role = Role::where('name', 'Member')->first();
            if ($role) {
                if (!empty($searchQuery)) {
                    $teamMembers = User::role($role)
                        ->with('numbers:user_id,phone_number', 'profile:id,user_id,contact_id,avatar')
                        ->where(function ($query) use ($searchQuery) {
                            $query->where('firstname', 'LIKE', '%' . $searchQuery . '%')
                                ->orWhere('lastname', 'LIKE', '%' . $searchQuery . '%');
                        })
                        ->get(['id', 'firstname', 'lastname', 'last_login_at']);
                } else {
                    $teamMembers = User::role($role)
                        ->with('numbers:user_id,phone_number', 'profile:id,user_id,contact_id,avatar')
                        ->get(['id', 'firstname', 'lastname', 'last_login_at']);
                }

                $totalRecord = count($teamMembers);
                $totalPage = ceil($totalRecord / $perPage);
                foreach ($teamMembers as $member) {
                    $member->fullName = $member->firstname . ' ' . $member->lastname;
                    $member->avatar = $member?->profile?->avatar ? asset('storage/' . $member->profile->avatar) : null;

                    $curtime = Carbon::now();
                    $dateTime = $member->last_login_at ? Carbon::parse($member->last_login_at) : $curtime;
                    $formattedDate = "Since " . $dateTime->format('j M, \a\t h:i A');
                    $member->last_login_at = $formattedDate;

                    $calls = Auth::user()?->calls();
                    $member->outboundCalls = $calls->whereIn('direction', ['outbound-api', 'outbound-dial'])->count();
                    $member->inboundCalls = $calls->where('direction', 'inbound')->count();
                }

                return response()->json([
                    'status' => true,
                    "teamMembers" => $teamMembers,
                    'totalPage' => $totalPage,
                    'totalRecord' => $totalRecord,
                    'page' => $currentPage,
                ]);
            }
        } catch (TwilioException $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function dash_live_calls()
    {
        $totalOutboundCalls = 0;
        $totalInboundCalls = 0;
        $totalLiveCalls = 0;
        $totalMissed = 0;

        $callRecords = Auth::user()?->calls()->select('direction')->get();
        foreach ($callRecords as $call) {
            switch ($call->direction) {
                case 'outbound-dial':
                case 'outbound-api':
                    $totalOutboundCalls++;
                    break;
                case 'inbound':
                    $totalInboundCalls++;
                    break;
                case 'in-progress':
                case 'completed':
                    $totalLiveCalls++;
                    break;
                case 'missed':
                    $totalMissed++;
                    break;
            }
        }

        return response()->json([
            'totalOutboundCalls' => $totalOutboundCalls,
            'totalInboundCalls' => $totalInboundCalls,
            'totalLiveCalls' => $totalLiveCalls,
            'totalMissed' => $totalMissed,
        ]);
    }

    public function dash_live_calls_past(Request $request)
    {
        if (!Auth::user()->numbers->count()) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any active number, please verify if you have an active number",
            ], 404);
        }

        $callType = $request->input('callType');
        $options = $request->input('options');

        try {
            $perPage = $options['itemsPerPage'];
            $currentPage = $options['page'] ?? 1;

            $phoneNumbers = Auth::user()?->numbers?->pluck('phone_number');

            $liveCallsPast = [];
            $now = Carbon::now();

            if ($callType !== 'queue') {
                foreach ($this->twilio->calls->read() as $call) {
                    if ($callType == 'live' && $call->status === 'in-progress' && in_array($call->from, $phoneNumbers->toArray())) {
                        $liveCallsPast[] = [
                            'callSid' => $call->sid,
                            'from' => $call->from,
                            'to' => $call->to,
                        ];
                    }

                    $callTo = false;
                    if (strpos($call->to, 'client:') === 0) {
                        $clientName = substr($call->to, 7);
                        if ($clientName == Auth::user()?->firstname) {
                            $callTo = true;
                        }
                    } else {
                        $callTo = in_array($call->to, $phoneNumbers->toArray());
                    }
                    if ($callType == 'recent' && in_array($call->from, $phoneNumbers->toArray()) || $callTo == true) {
                        $callTime = Carbon::createFromTimestamp($call->dateCreated->getTimestamp());
                        if ($now->diffInMinutes($callTime) <= 30) {
                            $liveCallsPast[] = [
                                'callSid' => $call->sid,
                                'from' => $call->from,
                                'to' => $call->to,
                                'callTime' => $callTime->format('Y-m-d H:i:s'),
                            ];
                        }
                    }
                }
            }

            if ($callType == 'queue') {
                foreach ($this->twilio->queues->read(20) as $member) {
                    $liveCallsPast[] = [
                        'callSid' => $member->sid,
                        'from' => $member->from,
                        'to' => $member->to,
                        'position' => $member->position,
                    ];
                }
            }

            $totalRecord = count($liveCallsPast);
            $totalPage = ceil($totalRecord / $perPage);

            return response()->json([
                'status' => true,
                "liveCallsPast" => $liveCallsPast,
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

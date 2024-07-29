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

        Log::info('inside VOiceController');
        
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
        if (!Auth::user()->numbers->count()) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any active number, please verify if you have an active number",
            ], 404);
        }

        $selectedItem = $request->input('selectedItem');
        $callTrait = $request->input('callTrait');
        $dateRange = $request->input('dateRange');
        $member = $request->input('member');
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        try {
            $perPage = $options['itemsPerPage'];
            $currentPage = $options['page'] ?? 1;
            $filter = [];

            if (!empty($searchQuery)) {
                $filter['to'] = $searchQuery;
            }
            if (!empty($callTrait) && is_array($callTrait)) {
                $filter['status'] = $callTrait;
            }

            // if (!empty($dateRange) && is_array($dateRange)) {
            //     // $start_date = $dateRange[0] ?? '';
            //     // $end_date = $dateRange[1] ?? '';
            //     // $filter['startTimeBefore'] = $start_date;
            //     // $filter['startTimeAfter'] = $end_date;
            //     $filter = [];
            //     dd($dateRange);
            // } 
            if (!empty($dateRange) && is_string($dateRange)) {

                $dateArray = explode('to', $dateRange);
                if (!empty($dateArray[0])) {
                    $str_start_date = $dateArray[0];
                    $sdateTime = new \DateTime($str_start_date);
                    $start_date = $sdateTime->format("Y-m-d");
                    $filter['startTimeBefore'] = $start_date;
                    if (count($dateArray) === 1) {
                        $filter['status'] = "completed";
                    }
                }
                if (!empty($dateArray[1])) {
                    $str_end_date = $dateArray[1];
                    $edateTime = new \DateTime($str_end_date);
                    $end_date = $edateTime->format("Y-m-d");
                    $filter['startTimeAfter'] = $end_date;
                }
            }
            if ($member === 'All members') {
                // $allMembers = ['member_phone_number_1', 'member_phone_number_2',];
                // $filter['from'] = $allMembers;
                $filter = [];
            }
            $twilioCalls = $this->twilio->calls->read($filter, 100);
            $totalRecord = count($twilioCalls);
            $totalPage = ceil($totalRecord / $perPage);

            $allCalls = [];
            foreach ($twilioCalls as $call) {

                //$recordings = $this->twilio->recordings->read(["callSid" => $call->sid]);
                //$recordingUrl = count($recordings) > 0 ? $recordings[0]->uri : '-';
                $recordingUrl = $call->sid? asset('storage/voicemail/' . $call->sid) : '-';

                $allCalls[] = [
                    'call_sid' => $call->sid,
                    'teamdialer_number' => $call->from,
                    'number' => $call->to,
                    'status' => $call->status ?? '-',
                    'direction' => $call->direction,
                    'date' => Auth::user()->calls->where('sid', $call->sid)->first() ? Carbon::parse(Auth::user()->calls->where('sid', $call->sid)->first()->created_at)->setTimezone('Asia/Karachi')->diffForHumans() : Carbon::parse($call->endTime)->setTimezone('Asia/Karachi')->diffForHumans(),
                    'duration' => $call->duration . " seconds" ?? '-',
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
        if (!Auth::user()->numbers->count()) {
            return response()->json([
                'status' => false,
                'message' => "You don't have any active number, please verify if you have an active number",
            ], 404);
        }

        $callType = $request->input('callType');
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        if ($this->isUserAdmin()) {
            try {

                $perPage = $options['itemsPerPage'];
                $currentPage = $options['page'] ?? 1;

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
                        
                        //$recordings = $this->twilio->recordings->read(["callSid" => $call->sid]);
                        //$recordingUrl = count($recordings) > 0 ? $recordings[0]->uri : '-';
                        $recordingUrl = $call->sid? asset('storage/voicemail/' . $call->sid) : '-';

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
                            'record' => $recordingUrl
                        ];
                    }
                }
                //                $startIndex = ($currentPage - 1) * $perPage;
                //                $slicedCalls = array_slice($allCalls, $startIndex, $perPage);
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

                $perPage = $options['itemsPerPage'];
                $currentPage = $options['page'] ?? 1;

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
                        
                        //$recordings = $this->twilio->recordings->read(["callSid" => $call->sid]);
                        //$recordingUrl = count($recordings) > 0 ? $recordings[0]->uri : '-';
                        $recordingUrl = $call->sid? asset('storage/voicemail/' . $call->sid) : '-';
                        
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
                            'record' => $recordingUrl
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

    public function dash_number_list(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');
        try {
            $perPage = $options['itemsPerPage'];
            $currentPage = $options['page'] ?? 1;

            if (!empty($searchQuery)) {
                $purchasedNumbers = $this->twilio->incomingPhoneNumbers->read([
                    'phoneNumber' => $searchQuery,
                    'limit' => 20,
                ]);
            } else {
                $purchasedNumbers = $this->twilio->incomingPhoneNumbers->read([], 20);
            }

            $totalRecord = count($purchasedNumbers);
            $totalPage = ceil($totalRecord / $perPage);
            $allNumbers = [];

            foreach ($purchasedNumbers as $number) {
                $phoneNumber = $number->phoneNumber;
                $callRecords = $this->twilio->calls->read([
                    'to' => $phoneNumber,
                    'limit' => 100,
                ]);

                $outbound = 0;
                $inbound = 0;
                $missed = 0;
                foreach ($callRecords as $call) {
                    if ($call->direction === 'outbound-dial' || $call->direction === 'outbound-api') {
                        $outbound++;
                    } elseif ($call->direction === 'inbound') {
                        $inbound++;
                    } elseif ($call->direction === 'missed') {
                        $missed++;
                    }
                }
                $flag_url = '';
                // $countryCode = UserNumber::where('phone_number', $phoneNumber)->value('country_code');
                $phoneNumberInfo = $this->twilio->lookups->v1->phoneNumbers($phoneNumber)
                    ->fetch(array('country-code', 'country'));
                if (!empty($phoneNumberInfo->countryCode)) {
                    $code = Str::lower($phoneNumberInfo->countryCode);
                    $flag_url = asset('images/flags/' . $code . '.png');
                }
                $allNumbers[] = [
                    'number' => $phoneNumber,
                    'friendly_name' => $number->friendlyName,
                    'flag_url' => $flag_url,
                    'outbound' => $outbound,
                    'inbound' => $inbound,
                    'missed' => $missed,
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
                $query->where('status', 'no-answer');
            default:
                return $query;
        }
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

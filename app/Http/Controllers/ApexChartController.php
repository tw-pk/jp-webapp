<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\AssignPhoneNumberService;
use App\Models\Call;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Contact;
use App\Models\UserNumber;
use Carbon\Carbon;
use Illuminate\Http\Response;

class ApexChartController extends Controller
{

    /**
     * @throws ConfigurationException
     */
    public function __construct()
    {
        $this->assignPhoneNumberService = new AssignPhoneNumberService();
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

    public function fetchApexChartReport(Request $request)
    {
        $member = $request->input('member');
        
        $totalOutboundCalls = [];
        $totalInboundCalls = [];
        $totalMissedCalls = [];

        $user = Auth::user();
        if (!empty($member) && $member !="All Members") {
            $user = User::find($member);
        }
        
        $numbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($user->id);
        if (!empty($numbers)) {
            $callRecords = Call::selectRaw("
                    DATE_FORMAT(created_at, '%Y-%m') AS month,
                    SUM(CASE WHEN direction IN ('outbound-dial', 'outbound-api') THEN 1 ELSE 0 END) AS outboundCalls,
                    SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) AS inboundCalls,
                    SUM(CASE WHEN status = 'no-answer' THEN 1 ELSE 0 END) AS missedCalls
                ")
                ->where(function ($query) use ($numbers) {
                    $query->whereIn('to', $numbers)
                        ->orWhereIn('from', $numbers);
                })
                ->whereBetween('created_at', [now()->subMonths(6)->startOfMonth(), now()->endOfMonth()]) 
                ->groupBy('month')
                ->orderBy('month')
                ->get();
   
            $monthlyData = [
                'outbound' => array_fill(0, 7, 0),
                'inbound' => array_fill(0, 7, 0),
                'missed' => array_fill(0, 7, 0),
            ];
            
            $months = $this->getLastSevenMonths();
            foreach ($callRecords as $record) {
                $index = array_search($record->month, $months); 
                if ($index !== false) {
                    $monthlyData['outbound'][$index] = intval($record->outboundCalls);
                    $monthlyData['inbound'][$index] = intval($record->inboundCalls);
                    $monthlyData['missed'][$index] = intval($record->missedCalls);
                }
            }
        }

        return response()->json([
            'yearMonths' => $months,
            'series' => [
                [
                    'name' => 'Outbound',
                    'data' => $monthlyData['outbound'],
                ],
                [
                    'name' => 'Inbound',
                    'data' => $monthlyData['inbound'],
                ],
                [
                    'name' => 'Missed',
                    'data' => $monthlyData['missed'],
                ],
            ],
        ]);
    }

    private function getLastSevenMonths()
    {
        $months = [];
        for ($i = 6; $i >= 0; $i--) {
            $months[] = date('Y-m', strtotime("-$i months"));
        }
        return $months;
    }

    public function fetchStatistics(Request $request)
    {
        $callMade = 0;
        $callAnswered = 0;
        $callNotPicked = 0;
        $callAbandoned = 0;
        $totalLiveCalls = 0;

        $userId = Auth::user()->id;
        $numbers = $this->assignPhoneNumberService->getAssignPhoneNumbers($userId);

        if (!empty($numbers)) {
            $callRecords = Call::selectRaw("
                SUM(CASE WHEN status IN ('completed', 'in-progress', 'ringing', 'queued') THEN 1 ELSE 0 END) AS callMade,
                SUM(CASE WHEN status IN ('completed', 'in-progress') THEN 1 ELSE 0 END) AS callAnswered,
                SUM(CASE WHEN status IN ('no-answer', 'busy', 'failed') THEN 1 ELSE 0 END) AS callNotPicked,
                SUM(CASE WHEN status = 'queued' THEN 1 ELSE 0 END) AS callAbandoned,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) AS liveCalls
                
            ")
            ->where(function ($query) use ($numbers) {
                $query->whereIn('to', $numbers)
                    ->orWhereIn('from', $numbers);
            })
            ->first();

            $callMade = intval($callRecords->callMade ?? 0);
            $callAnswered = intval($callRecords->callAnswered ?? 0);
            $callNotPicked = intval($callRecords->callNotPicked ?? 0);
            $callAbandoned = intval($callRecords->callAbandoned ?? 0);
            $totalLiveCalls = intval($callRecords->liveCalls ?? 0);
            
        }

        $statistics = [
            [
                'title' => 'Call Made',
                'stats' => $callMade,
                'detailedStats' => '18min 30sec',
                'icon' => 'tabler-chart-pie-2',
                'color' => 'primary',
                'tonal' => 'warning',
                'background' => '__light_primary',
            ],
            [
                'title' => 'Call Answered',
                'stats' => $callAnswered,
                'detailedStats' => '10min 30sec',
                'icon' => 'tabler-chart-pie-2',
                'color' => 'primary',
                'tonal' => 'info',
                'background' => '__light_primary',
            ],
            [
                'title' => 'Avg Answer Time',
                'stats' => '9sec',
                'icon' => 'tabler-chart-pie-2',
                'color' => 'primary',
                'tonal' => 'success',
                'background' => '__light_primary',
            ],
            [
                'title' => 'Call Outside Office Hours',
                'stats' => '4',
                'icon' => 'tabler-chart-pie-2',
                'color' => 'primary',
                'tonal' => 'warning',
                'background' => '__light_primary',
            ],
            [
                'title' => 'Call Not Picked by Agent',
                'stats' => $callNotPicked,
                'icon' => 'tabler-chart-pie-2',
                'color' => 'primary',
                'tonal' => 'error',
                'background' => '__light_primary',
            ],
            [
                'title' => 'Calls Abandoned Before Ringing',
                'stats' => $callAbandoned,
                'icon' => 'tabler-chart-pie-2',
                'color' => 'primary',
                'tonal' => 'warning',
                'background' => '__light_primary',
            ],
        ];

        return response()->json([
            'status' => true,
            'statistics' => $statistics
        ]);

    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PhoneSetting;
use Illuminate\Support\Facades\Auth;
use App\Models\AssignNumber;
use App\Models\TeamMember;

class SettingController extends Controller
{

    public function fetch_setting(Request $request)
    {
        $phoneSetting = PhoneSetting::where('user_id', Auth::user()->id)
            ->where('phone_number', $request->phone_number)
            ->first();

        if (!empty($phoneSetting->ring_order_value)) {
            $phoneSetting->ring_order_value = unserialize($phoneSetting->ring_order_value);
        }

        $assignNumbers = AssignNumber::with(['invitation' => function ($query) {
            $query->select('id', 'firstname', 'lastname');
        }])
            ->where('phone_number', $request->phone_number)
            ->get();

        $assignUsers = [];
        $uniqueIds = [];
        foreach ($assignNumbers as $assignNumber) {

            if ($assignNumber?->invitation?->id && !in_array($assignNumber->invitation->id, $uniqueIds)) {
                $assignUsers[] = [
                    'invitationId' => $assignNumber->invitation->id,
                    'fullname' => $assignNumber?->invitation?->firstname . ' ' . $assignNumber?->invitation?->lastname,
                    'webDesktop' => false,
                    'mobileLandline' => false,
                ];
                $uniqueIds[] = $assignNumber->invitation->id;
            }

            if ($assignNumber->team_id) {
                $teamMembers = TeamMember::with(['invitation' => function ($query) {
                    $query->select('id', 'firstname', 'lastname');
                }])
                    ->where('team_id', $assignNumber->team_id)
                    ->get();

                foreach ($teamMembers as $teamMember) {
                    $invitation = $teamMember->invitation;
                    if ($invitation->id && !in_array($invitation->id, $uniqueIds)) {
                        $assignUsers[] = [
                            'invitationId' => $invitation->id,
                            'fullname' => $invitation->firstname . ' ' . $invitation->lastname,
                            'webDesktop' => false,
                            'mobileLandline' => false,
                        ];
                        $uniqueIds[] = $invitation->id;
                    }
                }
            }
        }
        usort($assignUsers, function ($a, $b) {
            return $b['invitationId'] - $a['invitationId'];
        });
        return response()->json([
            'message' => 'Phone number setting fetched successfully',
            'phoneSetting' => $phoneSetting,
            'assignUsers' => $assignUsers
        ]);
    }

    public function add_routing(Request $request)
    {
        try {
            PhoneSetting::updateOrInsert(
                ['user_id' => Auth::user()->id, 'phone_number' => $request->phone_number],
                [
                    'user_id' => Auth::user()->id,
                    'phone_number' => $request->phone_number,
                    'fwd_incoming_call' => $request->fwd_incoming_call,
                    'unanswered_fwd_call' => $request->unanswered_fwd_call,
                    'ring_order' => $request->ringOrder,
                    'ring_order_value' => !empty($request->ringOrderValue) ? serialize($request->ringOrderValue) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            return response()->json([
                'message' => 'Call Routing have been saved Successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while adding data'], 500);
        }
    }

    public function add_callerids(Request $request)
    {
        try {
            PhoneSetting::updateOrInsert(
                ['user_id' => Auth::user()->id, 'phone_number' => $request->phone_number],
                [
                    'user_id' => Auth::user()->id,
                    'phone_number' => $request->phone_number,
                    'incoming_caller_id' => $request->incoming_caller_id,
                    'outbound_caller_id' => $request->outbound_caller_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            return response()->json([
                'message' => 'Caller Id have been saved Successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while adding data'], 500);
        }
    }

    public function add_voicemail(Request $request)
    {
        try {
            PhoneSetting::updateOrInsert(
                ['user_id' => Auth::user()->id, 'phone_number' => $request->phone_number],
                [
                    'user_id' => Auth::user()->id,
                    'phone_number' => $request->phone_number,
                    'vunanswered_fwd_call' => $request->vunanswered_fwd_call,
                    'vemail_id' => $request->vemail_id,
                    'voice_message' => $request->voice_message,
                    'transcription' => $request->transcription,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            return response()->json([
                'message' => 'Voice Mail have been saved Successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while adding data'], 500);
        }
    }
}

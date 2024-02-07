<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DialerSetting;
use Illuminate\Support\Facades\Auth;

class DialerSettingController extends Controller
{
    public function dialer_setting_save(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $dialerSetting = DialerSetting::where('user_id', Auth::user()->id)->first();
        if (!$dialerSetting) {
            $dialerSetting = new DialerSetting();
        }
        $dialerSetting->user_id = Auth::user()->id;

        if($request->type =='country_outbound_calls'){
            $dialerSetting->country_outbound_calls = $request->country_outbound_calls;
        }
        if($request->type =='number_outbound_calls'){
            $dialerSetting->number_outbound_calls = $request->number_outbound_calls;
        }
        if($request->type =='number_outbound_sms'){
            $dialerSetting->number_outbound_sms = $request->number_outbound_sms;
        }
        if($request->type =='active_noise_cancellation'){
            $dialerSetting->active_noise_cancellation = $request->active_noise_cancellation;
        }
        $dialerSetting->save();
        
        return response()->json([
            'message' => 'The setting has been saved Successfully!'
        ], 200);
    }

    public function dialer_setting()
    {
        $dialerSetting = DialerSetting::where('user_id', Auth::user()->id)?->first();
        return response()->json([
            'setting' => $dialerSetting
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\CreditProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreditController extends Controller
{
    public function fetch_top_up_credit(Request $request)
    {
        if(Auth::user()->credit){
            return response()->json([
                'credit' =>  '$' . number_format(Auth::user()->credit->credit, 2),
                'autoCredit' => Auth::user()->credit->threshold_enabled,
                'threshold_value' => CreditProduct::where('price_id', Auth::user()->credit->threshold_value)->first()?->id,
                'recharge_value' => CreditProduct::where('price_id', Auth::user()->credit->recharge_value)->first()?->id,
            ]);
        }else{
            return response()->json([
                'credit' =>  '$' . number_format(0, 2)
            ]);
        }
    }

    public function fetch_top_limits(){
        $credit_products = CreditProduct::all();
        $credit_limits = [];
        foreach ($credit_products as $credit_product) {
            $credit_limits[] = [
                'price' => '$'.number_format($credit_product->price, 2),
                'id' => $credit_product->id
            ];
        }

        return response()->json($credit_limits);
    }

    public function update_credit_info(Request $request){
        
        $request->validate([
            'autoCreditEnabled' => 'required'
        ]);
        $autoCreditValue = CreditProduct::find($request->autoCreditPrice);
        $rechargeAmount = CreditProduct::find($request->rechargeAmount);
       
        if(!$autoCreditValue){
            return response()->json([
                'status' => false,
                'message' => 'No credit information found!'
            ], 404);
        }

        if(!$rechargeAmount){
            return response()->json([
                'status' => false,
                'message' => 'No credit information found!'
            ], 404);
        }

        if(Auth::user()->credit){
            Auth::user()->credit->threshold_enabled = $request->autoCreditEnabled;
            Auth::user()->credit->threshold_value = $autoCreditValue->price_id;
            Auth::user()->credit->recharge_value = $rechargeAmount->price_id;
            Auth::user()->credit->save();

            return response()->json([
                'status' => true,
                'message' => 'Credit information updated successfully!'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'No credit information found!'
            ], 404);
        }
    }

    public function add_top_up_credit(Request $request)
    {

        return response()->json([
            'message' => 'Top Up Credit Added Successfully',
        ]);
    }

    public function fetch_credit_payment(Request $request)
    {
        return response()->json([
            'message' => 'Credit Payment fetched successfully',
        ]);
    }

    public function add_credit_payment(Request $request)
    {
        dd($request->all());
        return response()->json([
            'message' => 'Credit Payment Added Successfully'
        ]);
    }
}

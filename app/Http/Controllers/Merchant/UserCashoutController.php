<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\CommonController;
use App\Http\Controllers\Helper\WalletTransaction;
use App\Http\Controllers\PaymentMethods\CreditAccountDetailController;
use App\Models\Configuration;
use App\Models\CreditAccountDetail;
use App\Models\User;
use App\Models\UserCashout;
use App\Models\InfoSetting;
use App\Models\Onesignal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\MerchantTrait;

class UserCashoutController extends Controller
{
    use MerchantTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'USER_CASHOUT')->first();
        view()->share('info_setting', $info_setting);
    }
    
    
    public function index(Request $request){
        try{
            $merchant_id = get_merchant_id();
            $user_cashout_requests = UserCashout::with('User')->where('merchant_id',$merchant_id)->latest()->paginate(20);
            return view('merchant.cashout.userIndex',compact('user_cashout_requests'));
        }catch (\Exception $e){
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
    
    public function changeStatus(Request $request, $id){
        try{

            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
            $user_cashout_request = UserCashout::with('User')->where('merchant_id',$merchant_id)->find($id);
            return view('merchant.cashout.userEdit',compact('user_cashout_request'));
        }catch (\Exception $e){
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
    
    public function changeStatusUpdate(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'cashout_status' => 'required',
            'action_by' => 'required',
            'transaction_id' => 'required',
            'comment' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withErrors($errors[0]);
        }
        DB::beginTransaction();
        try{
            $user_cashout_request = UserCashout::find($id);
            $user = User::find($user_cashout_request->user_id);
            $string_file = $this->getStringFile(NULL,$user_cashout_request->Merchant);
            if($request->cashout_status == 2){
                $paramArray = array(
                    'user_id' => $user->id,
                    'booking_id' => NULL,
                    'amount' => $user_cashout_request->amount,
                    'narration' => 15,
                );

                $data = array(
                    'notification_type' => "WALLET_HISTORY",
                    'segment_type' => "WALLET_HISTORY",
                    'segment_data' => [],
                );

                $large_icon = "";
                $title = trans("$string_file.cashout_request");
                $message = trans("$string_file.cashout_request_rejected");
                $arr_param = ['user_id'=>$user->id,'data'=>$data,'message'=>$message,'merchant_id'=>$user->merchant_id,'title'=>$title,'large_icon'=>$large_icon];
                Onesignal::UserPushMessage($arr_param);

            }
            $user_cashout_request->cashout_status = $request->cashout_status;
            $user_cashout_request->action_by = $request->action_by;
            $user_cashout_request->transaction_id = $request->transaction_id;
            $user_cashout_request->comment = $request->comment;
            $success_message = trans("$string_file.success");
            if($request->cashout_status == 1){

                $data = array(
                    'notification_type' => "WALLET_UPDATE",
                    'segment_type' => "WALLET_UPDATE",
                    'segment_data' => [],
//                    'notification_gen_time' => time(),
                );

                $paramArray = array(
                    'user_id' => $user->id,
                    'booking_id' => null,
                    'amount' => $user_cashout_request->amount,
                    'payment_request'=>'Bank Transfer',
                    'display_payment'=>'Bank Transfer',
                    'narration' => 8,
                );
                WalletTransaction::UserWalletDebit($paramArray);
                
                $large_icon = "";
                $title = trans("$string_file.cashout_request_accepted");
                $message = trans("$string_file.cashout_request_accepted_message");
                $arr_param = ['user_id'=>$user->id,'data'=>$data,'message'=>$message,'merchant_id'=>$user->merchant_id,'title'=>$title,'large_icon'=>$large_icon];
                Onesignal::UserPushMessage($arr_param);
            }
            $user_cashout_request->save();
            DB::commit();
            return redirect()->route('merchant.user.cashout_request')->withSuccess($success_message);
        }catch (\Exception $e){
            DB::rollBack();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
}
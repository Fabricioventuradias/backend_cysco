<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Merchant;
use App\Models\PaymentMethod;
use App;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use DB;
use App\Models\MerchantPaymentMethodSegment;

class PaymentMethodController extends Controller
{
    use ImageTrait,MerchantTrait;
    public function __construct()
    {
        $info_setting = App\Models\InfoSetting::where('slug', 'PAYMENT_METHOD')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index()
    {
        $merchant = get_merchant_id(false);
        $payment = $merchant->PaymentMethod;
        return view('merchant.payment_methods.index', compact('payment','merchant'));
    }

    public function edit($id)
    {
        $merchant = get_merchant_id(false);
        $payment = PaymentMethod::where('id',$id)->first();
        $icon = get_image($payment->payment_icon,'payment_icon',$merchant->id,false);
        $merchant_payment = $payment->Merchant->where('id',$merchant->id);
        $merchant_payment = collect($merchant_payment->values());
        $sequence = 0;
        if(isset($merchant_payment))
        {
            if(!empty($merchant_payment[0]->pivot['icon'])){
                $icon = get_image($merchant_payment[0]->pivot['icon'],'p_icon',$merchant->id);
            }
           $sequence = $merchant_payment[0]->pivot['sequence'];
        }
        $payment_option_based_on_segment = $merchant->Configuration->payment_option_based_on_segment ?? 2;
        $merchant_segment = "";
        $selected_segments = "";
        $selected_sources = "";
        $pos_module_enable = $merchant->BookingConfiguration->pos_module_enable ?? 2;
        if($payment_option_based_on_segment == 1){
            $merchant_segment = $this->getMerchantSegmentServices($merchant->id);
            $selected_segments = MerchantPaymentMethodSegment::where('payment_method_id', $payment->id)->where('merchant_id', $merchant->id)->pluck('segment_id')->toArray();

            // Get selected source per segment^M
            $selected_sources = MerchantPaymentMethodSegment::where('payment_method_id', $payment->id)
                ->where('merchant_id', $merchant->id)
                ->pluck('source', 'segment_id')
                ->toArray();
        }
        return view('merchant.payment_methods.edit', compact('payment','merchant','icon','merchant_segment','selected_segments','pos_module_enable', 'selected_sources','sequence'));
    }

   public function update(Request $request, $id)
    {
        $merchant_id = get_merchant_id();
        $string_file = $this->getStringFile($merchant_id);
        $request->validate([
            'payment_name' => 'required'
        ]);
        if($request->hasFile('p_icon_image'))
        {
            $p_icon = $this->uploadImage('p_icon_image','p_icon',$merchant_id);
            DB::table('merchant_payment_method')->where([['payment_method_id','=',$id],['merchant_id','=',$merchant_id]])->update(['icon'=>$p_icon]);
        }
        DB::table('merchant_payment_method')
        ->where([['payment_method_id','=',$id],['merchant_id','=',$merchant_id]])
        ->update(['sequence' => $request->input('sequence', 0)]);
        
        App\Models\PaymentMethodTranslation::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'payment_method_id' => $id
        ], [
            'name' => $request->payment_name,
        ]);
        $pos_module_enable = \App\Models\Merchant::find($merchant_id)->BookingConfiguration->pos_module_enable ?? 2;

        MerchantPaymentMethodSegment::where('merchant_id', $merchant_id)
            ->where('payment_method_id', $id)
            ->delete();
        
        if ($request->has('segment_id')) {
            $segment_sub_groups = collect($this->getMerchantSegmentServices($merchant_id))
            ->pluck('sub_group_for_app', 'segment_id')
            ->toArray();
            $segmentIds = $request->segment_id;
            foreach ($segmentIds as $segment_id) {
                $source = '1'; // default app
                
                if ($pos_module_enable == 1) {
                    $source = $request->input('segment_source_' . $segment_id, '1');
                }
            
                MerchantPaymentMethodSegment::create([
                    'merchant_id'       => $merchant_id,
                    'payment_method_id' => $id,
                    'segment_id'        => $segment_id,
                    'source'            => $source,
                ]);
            }
        }

        return redirect()->route('merchant.paymentMethod.index')->withSuccess(trans("$string_file.saved_successfully"));
    }
}

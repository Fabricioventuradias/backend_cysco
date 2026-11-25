<?php

namespace App\Http\Controllers\Merchant\AirFreight;

use App\Models\AdvertisementBanner;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\InfoSetting;
use App\Models\Merchant;
use App\Traits\ImageTrait;
use App\Traits\AirFreightBookingTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\AirFreightServiceType;
use App\Models\AirFreightBooking;
use App\Models\AirFreightBookingDocument;
use App\Models\Segment;
use App\Models\User;
use App\Models\Onesignal;
use App\Http\Controllers\Helper\AirFreightBookingDataController;
use App\Http\Controllers\Helper\WalletTransaction;

class AirFreightBookingController extends Controller
{
    use ImageTrait,MerchantTrait,AirFreightBookingTrait;
    
    public function index(){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = get_merchant_id();
        $bookings = AirFreightBooking::where('merchant_id',$merchant_id)->orderBy('created_at', 'DESC')->paginate(10);
        $data = [];
        $data['export_search'] = [];
        $data['merchant_id'] = $merchant_id;
        return view('merchant.air_freight_booking.index', compact('bookings','data'));
    }
    
    public function bookingDetails(Request $request,$id){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = get_merchant_id();
        $booking = AirFreightBooking::where('merchant_id',$merchant_id)->where('id',$id)->first();
        $arr_booking_status = $this->getBookingStatus($string_file);
        $documentData = AirFreightBookingDocument::where('checkout_id',$booking->checkout_id)->get();
        return view('merchant.air_freight_booking.booking_details',compact('booking','documentData','arr_booking_status'));
    }
    
    public function bookingConfirmWithDoc(Request $request,$id){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = get_merchant_id();
        $booking = AirFreightBooking::where('merchant_id',$merchant_id)->where('id',$id)->first();
        $documentData = AirFreightBookingDocument::where('checkout_id',$booking->checkout_id)->get();
        foreach($documentData as $document){
            $document->booking_id = $booking->id;
            $document->document_verification_status = 1;
            $document->save();
        }
        
        $booking->booking_status = 1002; // ride confirm booking
        $booking->save();
        
        // if ($booking->payment_status == 1) {
        //         try {
        //             event(new SendUserInvoiceMailEvent($booking));
        //         } catch (Exception $e) {
        //             p($e->getMessage());
        //         }
        // }
        $bookingData = new AirFreightBookingDataController();
        $notification_data = $bookingData->BookingNotificationForUser($booking, "END_RIDE");
        
        return redirect()->route('air_freight.booking.details',$booking->id)->with('success',trans("$string_file.ride_booked"));
    }
    
    public function bookingRejected(Request $request){
        $request->validate([
            'booking_id' => 'required',
            'comment' => 'required',
            'request_from' => 'required',
        ]);
        
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = get_merchant_id();
        $booking = AirFreightBooking::where('merchant_id',$merchant_id)->where('id',$request->booking_id)->first();
        $documentData = AirFreightBookingDocument::where('checkout_id',$booking->checkout_id)->get();
        
        foreach($documentData as $document){
            $document->document_verification_status = 3;
            $document->save();
        }
        
        $booking->booking_status = 1004; //ride rejected by admin
        $booking->is_rejected = 1;
        $booking->rejected_reason = $request->comment;
        $booking->save();
        
        if($booking->payment_method_id == 3){
            $paramArray = array(
                'user_id' => $booking->user_id,
                'air_booking_id' => $booking->id,
                'amount' => $booking->final_amount_paid,
                'narration' => 12,
                'platform' => 2,
                'payment_method' => 3,
            );
            WalletTransaction::UserWalletCredit($paramArray);
        }
        
        $bookingData = new AirFreightBookingDataController();
        $notification_data = $bookingData->BookingNotificationForUser($booking, "REJECT_RIDE");
        
        return redirect()->route('air_freight.booking.details',$booking->id)->with('success',trans("$string_file.ride_rejected"));
        
        
        
    }
    
    
}
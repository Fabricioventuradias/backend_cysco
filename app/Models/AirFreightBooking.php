<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirFreightBooking extends Model
{
    protected $guarded = [];
    
    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->merchant_booking_id = $model->NewBookigId($model->merchant_id);
            return $model;
        });
    }

    public function NewBookigId($merchantID)
    {
        $booking = AirFreightBooking::where([['merchant_id', '=', $merchantID]])->lockForUpdate()->orderBy('id', 'DESC')->first();
        if (!empty($booking)) {
            return $booking->merchant_booking_id + 1;
        } else {
            return 1;
        }
    }
    
    public function Merchant(){
        return $this->belongsTo(Merchant::class);
    }
    
    public function CategoryService(){
        return $this->belongsTo(CategoryAirFreightServiceType::class,'air_freight_category_service_id','id');
    }
    
    public function User(){
        return $this->belongsTo(User::class);
    }
    
    public function AirFreightCheckout(){
        return $this->belongsTo(AirFreightCheckout::class,'checkout_id');
    }
    
    public function PaymentMethod(){
        return $this->belongsTo(PaymentMethod::class);
    }
    
    public function AirFreightBookingDocument(){
        return $this->hasMany(AirFreightBookingDocument::class,'booking_id','id');
    }
    
    public function getUserBooking($request){
        $user_id = $request->user('api')->id;
        $request_type = $request->request_type;
        $query = AirFreightBooking::select('id', 'final_amount_paid','payment_status','checkout_id','merchant_booking_id', 'user_id', 'merchant_id', 'segment_id', 'air_freight_service_type_id','air_freight_category_service_id','bill_details','booking_timestamp', 'booking_status', 'payment_method_id', 'created_at')
            ->with(['AirFreightCheckout','CategoryService'
                // 'AirFreightCheckout.AirFreightBookingDoc.Document',
                // 'AirFreightCheckout.AirFreightStopSource',
                // 'AirFreightCheckout.AirFreightStopDestination',
                // 'CategoryService.AirFreightServiceType',
                // 'CategoryService.Category',
                // 'CategoryService.AirFreightWeightParameter',
                // 'PaymentMethod',
            ])
            ->where([['user_id', '=', $user_id]])
            ->orderBy('created_at', 'DESC');
        if ($request_type == "PAST") {
            $query->where(function ($q) {
                $q->whereIn('booking_status', array(1002,1004, 1005, 1016, 1018));
            });
        } elseif ($request_type == "ACTIVE") {
            $query->where(function ($q) {
                $q->whereIn('booking_status', array(1001));
            });
        }
        $bookings = $query->latest()->paginate(10);
        
        return $bookings;
    }
}






<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;  // Change this
use App;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Helper\CommonController;

class Fleet extends Authenticatable
{
    use HasFactory,HasApiTokens;
    protected $hidden = [
        'password',
    ];
    protected $guarded = [];
    
    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class);
    }
    
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function LanguageFleetAny()
    {
        return $this->hasOne(LanguageFleet::class);
    }

    public function LanguageFleetSingle()
    {
        return $this->hasOne(LanguageFleet::class)->where([['locale', '=', App::getLocale()]]);
    }

    public function getFleetNameAttribute()
    {
        if (empty($this->LanguageFleetSingle)) {
            return $this->LanguageFleetAny->fleet_name;
        }
        return $this->LanguageFleetSingle->fleet_name;
    }

    public function getFleetOwnerNameAttribute()
    {
        if (empty($this->LanguageFleetSingle)) {
            return $this->LanguageFleetAny->fleet_owner_name;
        }
        return $this->LanguageFleetSingle->fleet_owner_name;
    }
    
    public function getAddressAttribute()
    {
        if (empty($this->LanguageFleetSingle)) {
            return $this->LanguageFleetAny->address;
        }
        return $this->LanguageFleetSingle->address;
    }

    public function getBankNameAttribute()
    {
        if (empty($this->LanguageFleetSingle)) {
            return $this->LanguageFleetAny->bank_name;
        }
        return $this->LanguageFleetSingle->bank_name;
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'fleet_id');
    }
    
    public function bookingTransactions()
    {
        return $this->hasManyThrough(
            BookingTransaction::class, // final model
            Booking::class,            // intermediate model
            'fleet_id',                // FK on bookings table (links to fleets)
            'booking_id',              // FK on booking_transactions table (links to bookings)
            'id',                      // local key on fleets table
            'id'                       // local key on bookings table
        );
    }
    
    public function findForPassport($user_cred = null)
    {
        $request = Request::instance();
        $loginType = $request->fleet_login_type == '1' ? 'EMAIL' : 'PHONE';
        if (!empty($_SERVER['HTTP_PUBLICKEY']) && !empty($_SERVER['HTTP_SECRETKEY'])) {
            $merchant = CommonController::MerchantObj($_SERVER['HTTP_PUBLICKEY'], $_SERVER['HTTP_SECRETKEY']);
            // $fleet_login = $merchant->FleetConfiguration->fleet_login ?? 'EMAIL';
            $merchant_id = $merchant['id'];
        }
            if ($loginType == "EMAIL") {
                $fleet = Fleet::where([
                    ['merchant_id', '=', $merchant_id],
                    ['fleet_owner_email', '=', $user_cred]
                ])->latest()->first();
            }else{
                $fleet = Fleet::where([
                    ['merchant_id', '=', $merchant_id],
                    ['fleet_owner_phone', '=', $user_cred]
                ])->latest()->first();
            }
        return $fleet; 
        
    }

}

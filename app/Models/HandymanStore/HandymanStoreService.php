<?php
namespace App\Models\HandymanStore;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceType;
use App\Models\ServiceTypeImage;
use App\Models\Segment;
use App\Models\HandymanCategory;
use App\Models\SegmentPriceCardDetail;

class HandymanStoreService  extends Model
{
    use HasFactory;
    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
    public function HandymanStoreService($service_type_id, $handyman_store_id)
    {
        $row = DB::table('merchant_service_type')
            ->where('service_type_id', $service_type_id)
            ->where('handyman_store_id', $handyman_store_id)
            ->first();
    
        return $row->service_icon ?? null;
    }
    public function HandymanStore()
    {
        return $this->belongsTo(HandymanStore::class);
    }
    public function handymanStoreImages($handyman_store_id)
    {
        return $this->hasMany(ServiceTypeImage::class, 'service_type_id', 'service_type_id')
            ->where('handyman_store_id', $handyman_store_id);
    }
    public function Segment()
    {
        return $this->belongsTo(Segment::class);
    }
    public function HandymanCategory()
    {
        return $this->belongsTo(HandymanCategory::class);
    }
    public function priceCardDetail()
    {
        return $this->hasOne(
            SegmentPriceCardDetail::class,
            'service_type_id',
            'id'
        );
    }
    public function ServiceTranslations()
    {
        return $this->hasMany(ServiceTranslation::class, 'service_type_id');
    }
    
    public function ServiceTranslation()
    {
        $locale = App::getLocale();
        return $this->hasOne(ServiceTranslation::class, 'service_type_id')
            ->where(function ($q) use ($locale) {
                $q->where('locale', $locale);
                $q->orWhere('locale', '!=', NULL);
            });
    }
    public function ServiceName($handyman_store_id = null)
    {
        $locale = App::getLocale();
    
        if ($this->relationLoaded('ServiceTranslations')) {
            $translations = $this->ServiceTranslations
                ->where('handyman_store_id', $handyman_store_id);
    
            $service = $translations->firstWhere('locale', $locale)
                ?? $translations->firstWhere('locale', 'en');
    
            return $service ? $service->name : $this->serviceName;
        }
    
        $service = $this->hasOne(ServiceTranslation::class, 'service_type_id')
            ->where(function ($q) use ($locale) {
                $q->where('locale', $locale)
                  ->orWhere('locale', 'en');
            })
            ->where('handyman_store_id', $handyman_store_id)
            ->orderByRaw(
                "CASE WHEN locale = ? THEN 1 ELSE 2 END",
                [$locale]
            )
            ->first();
    
        return $service ? $service->name : $this->serviceName;
    }
    public function ServiceDescription($handyman_store_id = null)
    {
        $locale = App::getLocale();
    
        if ($this->relationLoaded('ServiceTranslations')) {
            $translations = $this->ServiceTranslations
                ->where('handyman_store_id', $handyman_store_id);
    
            $service = $translations->firstWhere('locale', $locale)
                ?? $translations->first();
    
            return $service ? $service->description : "";
        }
    
        $service = $this->hasOne(ServiceTranslation::class, 'service_type_id')
            ->where(function ($q) use ($locale) {
                $q->where('locale', $locale);
            })
            ->where('handyman_store_id', $handyman_store_id)
            ->first();
    
        if (empty($service)) {
            $service = $this->hasOne(ServiceTranslation::class, 'service_type_id')
                ->where(function ($q) {
                    $q->whereNotNull('locale');
                })
                ->where('handyman_store_id', $handyman_store_id)
                ->first();
        }
    
        return $service ? $service->description : "";
    }
    public function SegmentPriceCardDetail()
    {
        return $this->hasOne(SegmentPriceCardDetail::class);
    }
    public function getIcon($service_type_id, $handyman_store_id)
    {
        $row = DB::table('merchant_service_type')
            ->where('service_type_id', $service_type_id)
            ->where('handyman_store_id', $handyman_store_id)
            ->first();
    
        return $row->service_icon ?? null;
    }
    public function HandymanStoreServiceSequance($service_type_id, $handyman_store_id,$segment_id,$merchant_id)
    {
        $row = DB::table('merchant_service_type')
            ->where('service_type_id', $service_type_id)
            ->where('handyman_store_id', $handyman_store_id)
            ->where('segment_id',$segment_id)
            ->where('merchant_id',$merchant_id)
            ->first();
        return $row ;
    }

}
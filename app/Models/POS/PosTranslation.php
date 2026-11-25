<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PosTranslation extends Model
{
    protected $table = 'pos_translations';

    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'field',
        'locale',
        'value',
        'merchant_id',
        'business_segment_id',
    ];

    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeForModel($query, string $type, int $id)
    {
        return $query->where('translatable_type', $type)
                     ->where('translatable_id', $id);
    }

    public function scopeForStore($query, $storeId)
    {
        return $query->where(function ($q) use ($storeId) {
            $q->where('business_segment_id', $storeId)
              ->orWhereNull('business_segment_id');
        });
    }
}

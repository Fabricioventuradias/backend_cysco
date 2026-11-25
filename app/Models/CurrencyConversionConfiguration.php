<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyConversionConfiguration extends Model
{
    protected $table = 'currency_conversion_configurations';

    protected $fillable = [
        'merchant_id',
        'country_id',
        'base_currency_code',
        'local_currency_code',
        'exchange_rate',
        'status',
    ];

    /**
     * Merchant this configuration belongs to.
     */
    public function Merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Country this configuration applies to.
     */
    public function Country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Scope: only active configs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantBankField extends Model
{
    use HasFactory;


    protected $fillable = [
        'merchant_id',
        'field_name',
        'display_text',
        'field_type',
        'is_required',
        'field_order',
        'field_options',
        'field_description',
        'validation_pattern',
        'min_length',
        'max_length',
        'is_active',
    ];

    protected $casts = [
        'field_options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * Scopes
     */
    public function scopeByMerchant($query, $merchant_id)
    {
        return $query->where('merchant_id', $merchant_id);
    }

    // public function scopeByCountry($query, $country_code)
    // {
    //     return $query->where('country_code', $country_code);
    // }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('field_order', 'asc');
    }

    public function scopeForMerchant($query, $merchant_id)
    {
        return $query->where('merchant_id', $merchant_id)
                     ->active()
                     ->ordered();
    }

    /**
     * Get field data formatted for API response
     */
    public function toFieldArray()
    {
        return [
            'key' => $this->field_name,
            'display_text' => $this->display_text,
            'type' => $this->field_type,
            'required' => $this->is_required,
            'options' => $this->field_options,
            'description' => $this->field_description,
            'pattern' => $this->validation_pattern,
            'min_length' => $this->min_length,
            'max_length' => $this->max_length,
        ];
    }
}
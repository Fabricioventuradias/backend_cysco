<?php

namespace App\Traits\POS;

use App\Models\POS\PosTranslation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTranslations
{
    /**
     * Polymorphic relationship to PosTranslation.
     * Now uses getMorphClass() so it picks up your override.
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(
            PosTranslation::class,
            'translatable',
            'translatable_type',
            'translatable_id'
        );
    }

    /**
     * Read translated value with fallback to base column.
     */
    public function translate(string $field, ?string $locale = null, ?int $storeId = null): ?string
    {
        $locale = $locale ?: 'en';

        // if ($locale === 'en') {
        //     return $this->{$field} ?? null;
        // }

        $type = $this->getTranslatableType();

        // 1. Store-specific
        if ($storeId) {
            $t = PosTranslation::where('translatable_type', $type)
                ->where('translatable_id', $this->id)
                ->where('field', $field)
                ->where('locale', $locale)
                ->where('business_segment_id', $storeId)
                ->first();
                
            if ($t) return $t->value;
        }

        // 2. Merchant-wide
        $t = PosTranslation::where('translatable_type', $type)
            ->where('translatable_id', $this->id)
            ->where('field', $field)
            ->where('locale', $locale)
            ->whereNull('business_segment_id')
            ->first();
        if ($t) return $t->value;

        // 3. Fallback to base column
        return $this->{$field} ?? null;
    }

    /**
     * Save/update a translation.
     */
    public function setTranslation(
        string $field,
        string $locale,
        string $value,
        int $merchantId,
        ?int $storeId = null
    ): PosTranslation {
        return PosTranslation::updateOrCreate(
            [
                'translatable_type'   => $this->getTranslatableType(),
                'translatable_id'     => $this->id,
                'field'               => $field,
                'locale'              => $locale,
                'business_segment_id' => $storeId,
            ],
            [
                'value'       => $value,
                'merchant_id' => $merchantId,
            ]
        );
    }

    /**
     * Get all translations grouped by locale.
     */
    public function getAllTranslations(): array
    {
        $result = [];
        $translations = PosTranslation::where('translatable_type', $this->getTranslatableType())
            ->where('translatable_id', $this->id)
            ->get();

        foreach ($translations as $t) {
            $result[$t->locale][$t->field] = $t->value;
        }
        return $result;
    }

    /**
     * Delete translations — by direct query, no relation dependency.
     */
    public function deleteTranslations(?string $locale = null): int
    {
        $query = PosTranslation::where('translatable_type', $this->getTranslatableType())
            ->where('translatable_id', $this->id);
        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->delete();
    }

    // ── Internal ─────────────────────────────────────────────

    protected function getTranslatableType(): string
    {
        if (property_exists($this, 'translationType')) {
            return $this->translationType;
        }
        $class = class_basename(static::class);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
    }
}
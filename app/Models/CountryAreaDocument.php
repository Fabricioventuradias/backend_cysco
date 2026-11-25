<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryAreaDocument extends Model
{
    use HasFactory;

    protected $table = 'country_area_document';
    protected $fillable = ['country_area_id', 'document_id', 'document_type'];
    public $timestamps = false;

    public function CountryArea()
    {
        return $this->belongsTo(CountryArea::class, 'country_area_id', 'id');
    }

    public function Document()
    {
        return $this->belongsTo(Document::class, 'document_id', 'id');
    }
}

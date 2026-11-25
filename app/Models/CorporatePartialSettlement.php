<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;
use App;

class CorporatePartialSettlement extends Authenticatable
{
    protected $table = 'corporate_partial_settlements';
    protected $guarded = [];
    public function CorprateInvoice()
    {
        return $this->belongsTo(CorporateInvoice::class);
    }
    public function corporateInvoice()
    {
        return $this->belongsTo(CorporateInvoice::class, 'corporate_invoice_id');
    }
}

<?php

namespace App\Http\Controllers\Merchant;

use App\Models\UtilityTransaction;
use Illuminate\Http\Request;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use App\Http\Controllers\Controller;
use Barryvdh\Snappy\Facades\SnappyPdf;
class UtilityTransactionController extends Controller
{
    use MerchantTrait,ImageTrait; 
    
    
    
    public function UtilityTransactionList(Request $request)
    {
        $merchant_id = get_merchant_id();

        $query = UtilityTransaction::with(['user:id,first_name,last_name,UserPhone'])
            ->where('merchant_id', $merchant_id);

if ($request->filled('search')) {
    $search = $request->search;

    $query->where(function ($q) use ($search) {
        $q->where('trans_id', 'like', "%$search%")
          ->orWhere('cell', 'like', "%$search%")
          ->orWhere('ext_txn_no', 'like', "%$search%")
          ->orWhere('amount', 'like', "%$search%")
          ->orWhere('msg', 'like', "%$search%")
          ->orWhereHas('user', function ($q2) use ($search) {
              $q2->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$search%"])
                 ->orWhere('first_name', 'like', "%$search%")
                 ->orWhere('last_name', 'like', "%$search%")
                 ->orWhere('UserPhone', 'like', "%$search%");
          });
    });
}

        //  Date filter
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        // Status filter
        if ($request->has('status')) {

            if ($request->status == '0') {
                // ✅ All Status → no filter
            } 
            elseif ($request->status === '') {
                // ✅ N/A → NULL values
                $query->whereNull('transaction_status');
            } 
            else {
                // ✅ 1–6
                $query->where('transaction_status', $request->status);
            }
        }

        $transactions = $query->orderBy('id', 'desc')->paginate(10);

        return view('merchant.utility-transaction.utility-transaction-list', compact('transactions'));
    }



}
<?php

namespace App\Http\Controllers\Merchant\HandymanStore;

use App\Http\Controllers\Controller;
use App\Models\HandymanStore\MerchantHandymanStoreSubscription;
use App\Models\HandymanStore\HandymanStoreSubscriptionPurchase;
use App\Models\HandymanStore\HandymanStore;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class StoreSubscriptionController extends Controller
{
    use MerchantTrait, ImageTrait;

    /* ── INDEX ── */
    public function index()
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $subscriptions = MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                            ->withCount('Purchases')
                            ->withCount([
                                'Purchases as active_purchases_count' => fn($q) => $q->where('status', 1),
                            ])
                            ->orderBy('sequence')
                            ->paginate(15);

        return view('merchant.handyman-store.subscription.index',
            compact('subscriptions', 'string_file')
        );
    }

    /* ── ADD / EDIT FORM ── */
    public function addEdit($id = null)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $subscription = null;
        if (!empty($id)) {
            $subscription = MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                                ->findOrFail($id);
        }

        return view('merchant.handyman-store.subscription.add-edit',
            compact('subscription', 'string_file')
        );
    }

    /* ── SAVE ── */
    public function save(Request $request, $id = null)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $validator = Validator::make($request->all(), [
            'subscription_name'  => 'required|string|max:100',
            'amount'             => 'required|numeric|min:0',
            'no_of_service_post' => 'required|integer|min:0',
            'validity_days'      => 'required|integer|min:0',  // ← added
            'status'             => 'required|in:0,1',
            'is_default'         => 'nullable|in:0,1',
            'logo'               => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'features'           => 'nullable|array',
            'sequence'           => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator->messages()->all());
        }

        DB::beginTransaction();
        try {
            if (!empty($id)) {
                $subscription = MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                                    ->findOrFail($id);
            } else {
                $subscription              = new MerchantHandymanStoreSubscription;
                $subscription->merchant_id = $merchant_id;
            }

            $subscription->subscription_name  = $request->subscription_name;
            $subscription->amount             = $request->amount;
            $subscription->no_of_service_post = $request->no_of_service_post;
            $subscription->validity_days      = $request->validity_days; 
            $subscription->status             = $request->status;
            $subscription->is_default         = $request->is_default ?? 0;
            $subscription->sequence           = $request->sequence    ?? 0;
            $subscription->features           = $request->features    ?? null;

            /* ── logo upload ── */
            if ($request->hasFile('logo')) {
                $subscription->logo = $this->uploadImage(
                    'logo',
                    'icon',
                    $merchant_id
                );
            }

            /* ── if set as default — unset others ── */
            if ($request->is_default == 1) {
                MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                    ->where('id', '!=', $subscription->id ?? 0)
                    ->update(['is_default' => 0]);
            }

            $subscription->save();

            DB::commit();

            return redirect()
                ->route('merchant.handyman-store.subscription.index')
                ->with('success', trans("$string_file.saved_successfully"));

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /* ── DELETE ── */
    public function destroy($id)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $subscription = MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                            ->findOrFail($id);

        $active = HandymanStoreSubscriptionPurchase::where('subscription_id', $id)
                    ->where('status', 1)
                    ->count();

        if ($active > 0) {
            return redirect()->back()->with('error',
                'Cannot delete. Active stores are using this plan.'
            );
        }

        $subscription->delete();

        return redirect()
            ->route('merchant.handyman-store.subscription.index')
            ->with('success', trans("$string_file.deleted_successfully"));
    }

    /* ── CHANGE STATUS ── */
    public function changeStatus($id, $status)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $subscription = MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                            ->findOrFail($id);

        $subscription->status = $status;
        $subscription->save();

        return redirect()->back()->with('success', trans("$string_file.updated_successfully"));
    }

    /* ── ASSIGN TO STORE ── */
    public function assignToStore(Request $request)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $validator = Validator::make($request->all(), [
            'handyman_store_id' => 'required|integer|exists:handyman_stores,id',
            'subscription_id'   => 'required|integer|exists:merchant_handyman_store_subscriptions,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages()->all());
        }

        DB::beginTransaction();
        try {
            $subscription = MerchantHandymanStoreSubscription::where('merchant_id', $merchant_id)
                                ->findOrFail($request->subscription_id);

            $store = HandymanStore::where('merchant_id', $merchant_id)
                        ->findOrFail($request->handyman_store_id);

            /* ── expire existing ── */
            HandymanStoreSubscriptionPurchase::where('handyman_store_id', $store->id)
                ->where('status', 1)
                ->update(['status' => 2]);

            /* ── create new purchase ── */
            HandymanStoreSubscriptionPurchase::create([
                'merchant_id'        => $merchant_id,
                'handyman_store_id'  => $store->id,
                'subscription_id'    => $subscription->id,
                'amount_paid'        => $subscription->amount,
                'no_of_service_post' => $subscription->no_of_service_post,
                'used_service_post'  => 0,
                'status'             => 1,
            ]);

            /* ── update store subscription_id ── */
            $store->subscription_id = $subscription->id;
            $store->save();

            DB::commit();

            return redirect()->back()->with('success', 'Subscription assigned successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /* ── PURCHASE HISTORY ── */
    public function purchaseHistory(Request $request)
    {
        $merchant    = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $purchases = HandymanStoreSubscriptionPurchase::with([
                'HandymanStore:id,full_name,business_logo',
                'Subscription:id,subscription_name,logo',
            ])
            ->where('merchant_id', $merchant_id)
            ->when(!empty($request->handyman_store_id),
                fn($q) => $q->where('handyman_store_id', $request->handyman_store_id)
            )
            ->orderByDesc('id')
            ->paginate(20);

        $stores = HandymanStore::where('merchant_id', $merchant_id)
                    ->select('id', 'full_name')
                    ->get();

        return view('merchant.handyman-store.subscription.purchase-history',
            compact('purchases', 'stores', 'string_file')
        );
    }
}
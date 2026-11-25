<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\HandymanStoreSubscriptionPlan;
use App\Models\HandymanStoreSubscriptionPlanTranslation;
use App\Models\HandymanStoreSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Validator;
use App\Traits\MerchantTrait;

class HandymanStoreSubscriptionPlanController extends Controller
{
    /**
     * List all plans for the current store.
     */
     use MerchantTrait;
    public function index()
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $plans = HandymanStoreSubscriptionPlan::where('merchant_id', $merchant_id)
            ->orderBy('sequence', 'ASC')
            ->orderBy('id', 'DESC')
            ->paginate(15);

        return view('merchant.handyman-store-subscription.index', compact('plans', 'string_file'));
    }

    /**
     * Show the create/edit form.
     */
    public function create(Request $request, $id = null)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(null, $merchant);

        $plan = null;
        if ($id != null) {
            $plan = HandymanStoreSubscriptionPlan::where('merchant_id', $merchant_id)
                ->with('translations')
                ->findOrFail($id);
        }

        $submit_button = $id == null ? 'Create' : 'Update';

        return view('merchant.handyman-store-subscription.form', compact('plan', 'string_file', 'submit_button', 'id'));
    }

    /**
     * Store or update a plan.
     */
    public function save(Request $request, $id = null)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;

        $rules = [
            'name'           => 'required|string|max:191',
            'price'          => 'required|numeric|min:0',
            'duration_days'  => 'required|integer|min:1',
            'discount_type'  => 'required|in:1,2',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0',
        ];

        // percentage must be 0..100
        // if ((int) $request->discount_type === HandymanStoreSubscriptionPlan::DISCOUNT_PERCENT) {
        //     $rules['discount_value'] = 'required|numeric|min:0|max:100';
        // }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput($request->input())
                ->withErrors($validator->messages()->all());
        }

        DB::beginTransaction();
        try {
            if ($id != null) {
                $plan = HandymanStoreSubscriptionPlan::where('merchant_id', $merchant_id)
                    ->findOrFail($id);
            } else {
                $plan = new HandymanStoreSubscriptionPlan();
                $plan->merchant_id       = $merchant_id;
            }

            $plan->name                = $request->name;
            $plan->slug                = Str::slug($request->name);
            $plan->description         = $request->description;
            $plan->price               = $request->price;
            $plan->duration_days       = $request->duration_days;
            $plan->discount_type       = $request->discount_type;
            $plan->discount_amount      = $request->discount_amount;
            // cap is only meaningful for percentage; null it out for fixed
            $plan->discount_percentage = (int) $request->discount_type === HandymanStoreSubscriptionPlan::DISCOUNT_PERCENT
                ? $request->discount_percentage
                : null;
            $plan->status              = $request->status ?? 1;
            $plan->included_benefits   = !empty($request->included_benefits) ? json_encode(array_values(array_filter($request->included_benefits))) : null;
            $plan->sequence            = $request->sequence ?? 0;
            $plan->save();

            // optional: store a translation for the current locale
            if (app()->getLocale()) {
                HandymanStoreSubscriptionPlanTranslation::updateOrCreate(
                    [
                        'handyman_store_subscription_plan_id' => $plan->id,
                        'locale' => app()->getLocale(),
                    ],
                    [
                        'name'        => $request->name,
                        'description' => $request->description,
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput($request->input())
                ->withErrors(['Something went wrong: ' . $e->getMessage()]);
        }

        return redirect()->route('merchant.subscription.handyman.store.index')
            ->with('success', $id == null ? 'Plan created successfully.' : 'Plan updated successfully.');
    }

    /**
     * Toggle status (1 active <-> 2 inactive).
     */
    public function status($id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;

        $plan = HandymanStoreSubscriptionPlan::where('merchant_id', $merchant_id)
            ->findOrFail($id);

        $plan->status = $plan->status == 1 ? 2 : 1;
        $plan->save();

        return redirect()->back()->with('success', 'Status updated.');
    }

    /**
     * Delete a plan (only if it has no subscriptions).
     */
    public function destroy($id)
    {
        $store = get_handyman_store(false);

        $plan = HandymanStoreSubscriptionPlan::where('merchant_id', $store->id)
            ->withCount('subscriptions')
            ->findOrFail($id);

        if ($plan->subscriptions_count > 0) {
            return redirect()->back()
                ->withErrors(['Cannot delete: this plan has active subscriptions. Set it inactive instead.']);
        }

        $plan->translations()->delete();
        $plan->delete();

        return redirect()->back()->with('success', 'Plan deleted.');
    }
    public function subscribers(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(null, $merchant);
    
        $subscriptions = HandymanStoreSubscription::with(['plan', 'User'])
            ->where('merchant_id', $merchant_id)
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('payment_status'), function ($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            })
            ->orderBy('id', 'DESC')
            ->paginate(15)
            ->withQueryString();   // keep filters across pagination
    
        return view('merchant.handyman-store-subscription.subscribers', compact('subscriptions', 'string_file'));
    }
    public function show(Request $request)
    {
    }
    
}
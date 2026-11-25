<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OfferDeal;
use App\Traits\MerchantTrait;
use App\Traits\ImageTrait;

class OfferDealController extends Controller
{
    use MerchantTrait, ImageTrait;

    /**
     * Display listing
     */
    public function index()
    {
        $merchant_id = get_merchant_id();

        $offerDeals = OfferDeal::where('merchant_id', $merchant_id)
            ->orderBy('id', 'desc')
            ->get();

        return view('merchant.offerdeal.index', compact('offerDeals'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('merchant.offerdeal.add');
    }

    // /**
    //  * Store data
    //  */
    public function store(Request $request)
    {
        $merchant_id = get_merchant_id();

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
            'tag'         => 'nullable|string|max:50',
            'img'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'link'        => 'nullable|url|max:500',
            'status'      => 'nullable|in:1,2',
            'holder_type' => 'required',
            'color_code' => 'required_if:type,ADVERTISEMENT_SMALL_MOVABLE_BANNER|nullable|regex:/^#[A-Fa-f0-9]{6}$/',
        ],
        [
            'img.max' => 'The image size must not be greater than 2 MB.',
        ]);

        // Upload Image
        if ($request->hasFile('img')) {
            $url = $this->uploadImage(
                'img',
                'offer_deals_image',
                $merchant_id
            );
        }

        if (isset($url)) {
            $validated['img'] = $url;
        }

        $validated['merchant_id'] = $merchant_id;
        $validated['status'] = $validated['status'] ?? 1; // default to active
        $validated['holder_type'] = $validated['holder_type'] ?? 1; // default to active
        $validated['color_code'] = $validated['color_code'] ?? 1; // default to active

        OfferDeal::create($validated);

        return redirect()
            ->route('merchant.offerdeal.index')
            ->with('success', 'Offer/Deal added successfully.');
    }

    public function edit($id)
    {
        $merchant_id = get_merchant_id();

        $offerDeal = OfferDeal::where('merchant_id', $merchant_id)
            ->findOrFail($id);

        return view('merchant.offerdeal.edit', compact('offerDeal'));
    }

    public function update(Request $request, $id)
    {
        $merchant_id = get_merchant_id();

        $offerDeal = OfferDeal::where('merchant_id', $merchant_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:100',
            'tag'         => 'required',
            'img'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'link'        => 'nullable|url|max:500',
            'status'      => 'nullable|in:1,2',
            'holder_type' =>  'required',
            'color_code' => 'required_if:type,ADVERTISEMENT_SMALL_MOVABLE_BANNER|nullable|regex:/^#[A-Fa-f0-9]{6}$/',
        ],
        [
            'img.max' => 'The image size must not be greater than 2 MB.',
        ]);

        // Update Image only if a new one is uploaded
        if ($request->hasFile('img')) {
            $url = $this->uploadImage(
                'img',
                'offer_deals_image',
                $merchant_id
            );
        }

        if (isset($url)) {
            $validated['img'] = $url;
        } else {
            unset($validated['img']); // keep existing image if none uploaded
        }

        $validated['status'] = $validated['status'] ?? $offerDeal->status;
        $validated['holder_type'] = $validated['holder_type'] ?? $offerDeal->holder_type;
        $validated['color_code'] = $validated['color_code'] ?? $offerDeal->color_code;
        

        $offerDeal->update($validated);

        return redirect()
            ->route('merchant.offerdeal.index')
            ->with('success', 'Offer/Deal updated successfully.');
    }

    public function destroy($id)
    {
        $merchant_id = get_merchant_id();

        $offerDeal = OfferDeal::where('merchant_id', $merchant_id)
            ->findOrFail($id);

        $offerDeal->delete();

        return redirect()
            ->route('merchant.offerdeal.index')
            ->with('success', 'Offer/Deal deleted successfully.');
    }

}
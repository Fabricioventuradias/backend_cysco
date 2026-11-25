<?php

namespace App\Http\Controllers\Merchant;
use App\Http\Controllers\Controller;

use App\Models\Merchant;
use App\Models\MerchantBankField;
use Illuminate\Http\Request;
use App\Traits\MerchantTrait;

class MerchantBankFieldsController extends Controller
{
    /**
     * Display list of fields for a merchant and country
     * GET /bank-fields
     */
    use MerchantTrait;
    public function index(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        
        $fields = MerchantBankField::where('merchant_id', $merchant_id)
            ->orderBy('field_order', 'asc')
            ->get();

        return view('merchant.bank-fields.index', [
            'merchant' => $merchant,
            'fields' => $fields,
            'field_types' => $this->getFieldTypes(),
        ]);
    }

    /**
     * Show create form for new field
     * GET /bank-fields/create
     */
    public function create()
    {
        return view('merchant.bank-fields.create', [
            'field_types' => $this->getFieldTypes(),
        ]);
    }

    /**
     * Store new field
     * POST /bank-fields
     */
    public function store(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);

        $validated = $request->validate([
            'field_name' => 'required|string|max:100|regex:/^[a-z_]+$/',
            'display_text' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,email,date,select,textarea,checkbox',
            'is_required' => 'nullable|boolean',
            'field_order' => 'nullable|integer',
            'field_options' => 'nullable|json',
            'field_description' => 'nullable|string',
            'validation_pattern' => 'nullable|string',
            'min_length' => 'nullable|integer',
            'max_length' => 'nullable|integer',
        ]);

        // Check if field already exists
        $existing = MerchantBankField::where('merchant_id', $merchant_id)
            ->where('field_name', $validated['field_name'])
            ->first();

        if ($existing) {
            return back()->with('error', trans("$string_file.this_field_already_exists"));
        }

        // Handle checkbox values - if not present in request, set to false
        $validated['merchant_id'] = $merchant_id;
        $validated['is_required'] = $request->has('is_required') ? true : false;
        $validated['is_active'] = true; // New fields are active by default

        MerchantBankField::create($validated);
        
        return redirect()->route('merchant.bank-fields.index')
            ->with('success', trans("$string_file.created_successfully"));
    }

    /**
     * Show edit form
     * GET /bank-fields/{field_id}/edit
     */
    public function edit($field_id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        
        $field = MerchantBankField::where('merchant_id', $merchant_id)
            ->findOrFail($field_id);

        return view('merchant.bank-fields.edit', [
            'merchant' => $merchant,
            'field' => $field,
            'field_types' => $this->getFieldTypes(),
        ]);
    }

    /**
     * Update field
     * PUT /bank-fields/{field_id}
     */
    public function update(Request $request, $field_id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile($merchant_id);
       
        $field = MerchantBankField::where('merchant_id', $merchant_id)
            ->findOrFail($field_id);

        $validated = $request->validate([
            'display_text' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,email,date,select,textarea,checkbox',
            'is_required' => 'nullable|boolean',
            'field_order' => 'nullable|integer',
            'field_options' => 'nullable|json',
            'field_description' => 'nullable|string',
            'validation_pattern' => 'nullable|string',
            'min_length' => 'nullable|integer',
            'max_length' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // FIX: Handle checkbox values properly
        // If checkbox is not in request, it means it was unchecked
        $validated['is_required'] = $request->has('is_required') ? true : false;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $field->update($validated);
        
        return redirect()->route('merchant.bank-fields.index')
            ->with('success',  trans("$string_file.created_successfully"));
    }

    

  
    public function reorder(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;

        $orders = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer',
            'orders.*.order' => 'required|integer',
        ]);

        foreach ($orders['orders'] as $item) {
            MerchantBankField::where('merchant_id', $merchant_id)
                ->where('id', $item['id'])
                ->update(['field_order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fields reordered successfully'
        ]);
    }

    /**
     * Helper: Get field types
     */
    private function getFieldTypes()
    {
        return [
            'text' => 'Text Input',
            'number' => 'Number Input',
            'email' => 'Email Input',
            'date' => 'Date Picker',
            'select' => 'Dropdown Select',
            'textarea' => 'Text Area',
            'checkbox' => 'Checkbox',
        ];
    }
}
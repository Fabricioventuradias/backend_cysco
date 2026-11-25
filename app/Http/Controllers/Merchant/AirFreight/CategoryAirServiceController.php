<?php

namespace App\Http\Controllers\Merchant\AirFreight;

use App\Models\InfoSetting;
use App\Models\Merchant;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\AirFreightServiceType;
use App\Models\Category;
use App\Models\AirFreightWeightParameter;
use App\Models\CategoryAirFreightServiceType;
use App\Models\AirFreightWeightParameterTranslation;
use View;
use App;

class CategoryAirServiceController extends Controller
{
    use ImageTrait,MerchantTrait;

    public function index(){
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL,$merchant);
        $merchant_id = $merchant->id;
    
        $categories = Category::where('merchant_id',$merchant_id)->get();
    
        $categoryServiceRecords = CategoryAirFreightServiceType::with([
                'AirFreightServiceType',
                'AirFreightWeightParameter.LanguageSingle',
                'AirFreightWeightParameter.LanguageAny',
            ])
            ->where('merchant_id', $merchant_id)
            ->get()
            ->groupBy('category_id');
    
        $categoryData = [];
        foreach ($categoryServiceRecords as $catId => $records) {
            $groupedByService = $records->groupBy('air_freight_service_type_id');
    
            $serviceLines = [];
            $weightLines = [];
    
            foreach ($groupedByService as $serviceTypeId => $recs) {
                $service = $recs->first()->AirFreightServiceType;
                $serviceLines[] = $service
                    ? trim($service->air_freight_type_name . ' - ' . $service->air_service_type_name)
                    : '';
    
                $weightLines[] = $recs->pluck('AirFreightWeightParameter')
                    ->filter()
                    ->unique('id')
                    ->map(function ($wp) {
                        return $wp->air_freight_weight_parameter_name;
                    })
                    ->filter()
                    ->implode(', ');
            }
    
            $categoryData[$catId] = [
                'service_lines' => $serviceLines,
                'weight_lines' => $weightLines,
            ];
        }
    
        $category_air_services = array_keys($categoryData);
    
        return view('merchant.category_air_service.index', compact('category_air_services','categories','categoryData'));
    }

    public function add(Request $request, $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $merchant_id = $merchant->id;
    
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['AIR_FREIGHT'], $all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
    
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
    
        try {
            $selected_category_id = null;
            $selected_service_weight_map = [];
            $selected_consolidation_service_types = [];
    
            if (!empty($id)) {
                $selected_records = CategoryAirFreightServiceType::where('merchant_id', $merchant_id)
                    ->where('category_id', $id)
                    ->get();
    
                if ($selected_records->isNotEmpty()) {
                    $selected_category_id = $selected_records->first()->category_id;
    
                    $selected_service_weight_map = $selected_records
                        ->whereNotNull('air_freight_weight_parameter_id')
                        ->groupBy('air_freight_service_type_id')
                        ->map(function ($records) {
                            return $records->pluck('air_freight_weight_parameter_id')->toArray();
                        })
                        ->toArray();
    
                    $selected_consolidation_service_types = $selected_records
                        ->whereNull('air_freight_weight_parameter_id')
                        ->pluck('air_freight_service_type_id')
                        ->unique()
                        ->values()
                        ->toArray();
                }
            }
    
            $airfreightservices = AirFreightServiceType::where('merchant_id', $merchant_id)->get();
            $weightParams = AirFreightWeightParameter::where('merchant_id', $merchant_id)->get();
            $categories = Category::where('merchant_id', $merchant_id)->get();
    
            return view('merchant.category_air_service.edit', compact(
                'airfreightservices', 'weightParams', 'categories',
                'selected_category_id', 'selected_service_weight_map', 'selected_consolidation_service_types'
            ));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }


    public function save(Request $request, $id = NULL)
    {
        try {
            $merchant = get_merchant_id(false);
            $merchant_id = $merchant->id;
    
            $request->validate([
                'air_freight_category' => 'required|integer',
                'air_weight_parameter' => 'array',
                'consolidation_service_type' => 'array',
            ]);
    
            $category = $request->input('air_freight_category');
            $weightParamsByServiceType = $request->input('air_weight_parameter', []);
            $consolidationServiceTypes = $request->input('consolidation_service_type', []);
    
            $hasCharterSelection = collect($weightParamsByServiceType)->contains(function ($wpIds) {
                return !empty($wpIds);
            });
            $hasConsolidationSelection = !empty($consolidationServiceTypes);
    
            if (!$hasCharterSelection && !$hasConsolidationSelection) {
                return redirect()->back()
                    ->withErrors('Please select at least one service type.')
                    ->withInput();
            }
    
            if ($id) {
                CategoryAirFreightServiceType::where('merchant_id', $merchant_id)
                    ->where('category_id', $id)
                    ->delete();
            }
    
            foreach ($weightParamsByServiceType as $serviceTypeId => $wpIds) {
                if (empty($wpIds)) {
                    continue;
                }
                foreach ($wpIds as $wpId) {
                    CategoryAirFreightServiceType::create([
                        'merchant_id' => $merchant_id,
                        'air_freight_service_type_id' => $serviceTypeId,
                        'category_id' => $category,
                        'air_freight_weight_parameter_id' => $wpId,
                    ]);
                }
            }
    
            foreach ($consolidationServiceTypes as $serviceTypeId) {
                CategoryAirFreightServiceType::create([
                    'merchant_id' => $merchant_id,
                    'air_freight_service_type_id' => $serviceTypeId,
                    'category_id' => $category,
                    'air_freight_weight_parameter_id' => null,
                ]);
            }
    
            return redirect()->route('merchant.category_air_service.index')->with('success', 'Saved successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

}
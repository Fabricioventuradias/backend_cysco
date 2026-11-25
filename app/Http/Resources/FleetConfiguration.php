<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\MerchantTrait;
use App\Models\CmsPage;

class FleetConfiguration extends JsonResource
{
    use MerchantTrait;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($data)
    {
        $request_data = $data->all();
        $string_file = $this->getStringFile($this->id);
        if (request()->device == 1) {
            $main_show_dialog = $this->Configuration->android_fleet_app_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->android_fleet_app_maintenance_mode == 1 ? trans("$string_file.android_fleet_app_maintenance_mode"): "";
            $version_show_dialog = $this->Configuration->android_fleet_app_version > request()->apk_version  ? true : false;
            $version_mandatory = $this->Configuration->android_fleet_app_mandatory_update == 1 ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("$string_file.app_update_warning") : '';
        } else {
            $main_show_dialog = $this->Configuration->ios_fleet_app_maintenance_mode == 1 ? true : false;
            $main_show_message = $this->Configuration->ios_fleet_app_maintenance_mode == 1 ? trans("$string_file.ios_fleet_app_maintenance_mode") : "";
            $version_mandatory = $this->Configuration->ios_fleet_app_mandatory_update == 1 ? true : false;
            $version_show_dialog =  $this->Configuration->ios_fleet_app_version > request()->apk_version ? true : false;
            $version_dialog_message = $version_show_dialog == true ? trans("$string_file.app_update_warning") : '';
        }

        $pages = CmsPage::where('merchant_id', $this->id)
            ->where('application', 4)
            ->where('slug', '!=', 'terms_and_Conditions')
            ->with(['Page.LanguagePages' => function ($q) {
                $q->where('merchant_id', $this->merchant_id);
            }])
            ->get();
        
        $terms = CmsPage::where('merchant_id', $this->id)
        ->where('application', 4)
        ->where('slug', 'terms_and_Conditions')
        ->first();
        
        if ($terms) {
            $pages->push($terms);
        }
        
        $cms_pages = $pages->map(function ($page) {
            return [
                'slug' => $page->slug,
                'name' => !empty($page->Page->Name($this->id))
                    ? $page->Page->Name($this->id)
                    : $page->page->page
            ];
        })->toArray();
        
        if(count($cms_pages) == 0){
            $cms_pages = CmsPage::where('merchant_id',$this->id)->where('application',4)
            ->with(['Page.LanguagePages' => function($q){
                $q->where('merchant_id', $this->merchant_id);
            }])
            ->get()
            ->map(function($page) {
                    return [
                        'slug' => $page->slug,
                        'name' => !empty($page->Page->Name($this->id)) ? $page->Page->Name($this->id) : $page->page->page
                    ];
                })->toArray();
        }
        
        return [
            'vehicle_ac_enable' => $this->Configuration->vehicle_ac_enable == 1 ? true : false,    
            'bank_details_enable' => $this->Configuration->bank_details_enable == 1 ? true : false,
            'login_type'=> $this->FleetConfiguration?->login_type, //1 EMail 2 Phone,
            'languages' => $this->Language,
            'cms_pages'=> $cms_pages,
            'app_version_dialog' => [
                'show_dialog' => $version_show_dialog,
                "mandatory" => $version_mandatory,
                "android_fleet_app_version" => $this->Configuration->android_fleet_app_version,
                "ios_fleet_app_version" => $this->Configuration->ios_fleet_app_version,
                "dialog_message" => $version_dialog_message,
                "ios_fleet_app_appid" => isset($this->Application->ios_fleet_app_appid) ? (string)($this->Application->ios_fleet_app_appid) : '',
                "ios_fleet_app_link" => isset($this->Application->ios_fleet_app_link) ? (string) ($this->Application->ios_fleet_app_link) : '',
            ],
            'app_maintainance' => [
                'show_dialog' => $main_show_dialog,
                'show_message' => $main_show_message
            ],
            'payment_type_during_assign_driver'=> isset($this->FleetConfiguration->payment_type_during_assign_driver) && $this->FleetConfiguration->payment_type_during_assign_driver == 1
        ];
    }
    
    public function with($data)
    {
        return [
            'result' => "1",
            'message' => "success",
        ];
    }
}
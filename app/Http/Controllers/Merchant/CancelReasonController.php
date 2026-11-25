<?php

namespace App\Http\Controllers\Merchant;

use App\Models\InfoSetting;
use Auth;
use App;
use App\Models\CancelReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Traits\MerchantTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use DB;
use App\Models\LanguageCancelReason;

class CancelReasonController extends Controller
{
    use MerchantTrait;
    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'CANCEL_REASON')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request $request)
    {
        $reasonTypeFor = $request->get('reason_type_for', 1);
        $merchant_id   = get_merchant_id();
    
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'], $all_food_grocery_clone);
        foreach ($all_segments as &$segment) {
            $segment = "cancel_reason_" . $segment;
        }
        unset($segment);
    
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
    
        $permission_segments = get_permission_segments(1, true);
    
        // 1) DB-level filters (NOT segment_id — that's applied after grouping)
        $rows = CancelReason::whereHas('Segment', function ($query) use ($permission_segments) {
                $query->whereIn('slag', $permission_segments);
            })
            ->where('merchant_id', $merchant_id)
            ->where('reason_type_for', $reasonTypeFor)
            ->when($request->filled('reason'), function ($query) use ($request) {
                $query->whereHas('LanguageAny', function ($q) use ($request) {
                    $q->where('reason', 'LIKE', '%' . trim($request->reason) . '%');
                });
            })
            ->orderBy('id')
            ->get();
    
        // 2) Collapse per-segment duplicates: same text + type + type_for = one reason
        $grouped = $rows->groupBy(function ($r) {
                return md5($r->ReasonName . '|' . $r->reason_type . '|' . $r->reason_type_for);
            })
            ->map(function ($group) {
                $first = $group->first();
                $first->setAttribute('segment_ids', $group->pluck('segment_id')->filter()->unique()->values());
                $first->setAttribute('grouped_ids', $group->pluck('id')->values());
                return $first;
            })
            ->values();
    
        // 3) Segment filter applied to the grouped set so the cell keeps all segments
        if ($request->filled('segment_id')) {
            $wanted = (array) $request->segment_id;
            $grouped = $grouped->filter(function ($r) use ($wanted) {
                return $r->segment_ids->intersect($wanted)->isNotEmpty();
            })->values();
        }
    
        // 4) Paginate the GROUPS
        $perPage = 15;
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $cancelreasons = new LengthAwarePaginator(
            $grouped->forPage($page, $perPage),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    
        $merchant_segments = get_permission_segments(1, false, get_merchant_segment());
    
        return view('merchant.cancelreason.index',
            compact('cancelreasons', 'merchant_segments', 'reasonTypeFor'));
    }

    public function create(Request $request)
    {
        $merchant_id = get_merchant_id();
          $reasonTypeFor = $request->get('reason_type_for', 1);
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $merchant_segments = get_permission_segments(1,false,get_merchant_segment());
        $storeCancelReasonShow=!empty(get_merchant_segment(true,null,2))? true:false;

        return view('merchant.cancelreason.add', compact('merchant_segments','reasonTypeFor','storeCancelReasonShow'));
    }

    public function store(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL,$merchant);
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'],$all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
        $request->validate([
            'reason_for' => 'required|integer',
            'reason' => 'required',
        ]);
        foreach($request->segment_id as $segment_id){
            $cancel_reason = CancelReason::create([
                'merchant_id' => $merchant_id,
                'segment_id' => $segment_id,
                'reason_type' => $request->reason_for,
                'reason_type_for' => $request->reason_type_for,
                'reason_status' => 1,
            ]);
            $this->SaveLanguageCancel($merchant_id, $cancel_reason->id, $request->reason);
        }
       return redirect()->route("cancelreason.index",['reason_type_for' => $request->reason_type_for])->withSuccess(trans("$string_file.added_successfully"));
    }

    public function SaveLanguageCancel($merchant_id, $cancel_reason_id, $reason)
    {
        App\Models\LanguageCancelReason::updateOrCreate([
            'merchant_id' => $merchant_id, 'locale' => App::getLocale(), 'cancel_reason_id' => $cancel_reason_id
        ], [
            'reason' => $reason,
        ]);
    }

    public function ChangeStatus($id, $status)
    {
        $validator = Validator::make(
            ['id' => $id, 'status' => $status],
            ['id' => ['required'], 'status' => ['required', 'integer', 'between:1,2']]
        );
        if ($validator->fails()) {
            return redirect()->back();
        }
    
        $merchant_id = Auth::user('merchant')->parent_id != 0
            ? Auth::user('merchant')->parent_id
            : Auth::user('merchant')->id;
    
        $cancelreason = CancelReason::where('merchant_id', $merchant_id)->findOrFail($id);
    
        $group = $this->getReasonSiblings($merchant_id, $cancelreason);
        foreach ($group as $row) {
            $row->reason_status = $status;
            $row->save();
        }
    
        return redirect()->route('cancelreason.index', ['reason_type_for' => $cancelreason->reason_type_for])
            ->with('success', "Status Updated");
    }

    public function edit($id)
    {
        $merchant_id = get_merchant_id();
    
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'], $all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
    
        $cancelreason = CancelReason::where('merchant_id', $merchant_id)->findOrFail($id);
    
        $group = $this->getReasonSiblings($merchant_id, $cancelreason);
        $selected_segment_ids = $group->pluck('segment_id')->map(fn($v) => (int) $v)->all();
    
        $merchant_segments = get_permission_segments(1, false, get_merchant_segment());
    
        return view('merchant.cancelreason.edit', compact(
            'cancelreason', 'merchant_segments', 'selected_segment_ids'
        ));
    }

    public function update(Request $request, $id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
    
        $all_food_grocery_clone = $this->getFoodGroceryClone($merchant_id);
        $all_segments = array_merge(['DELIVERY','TAXI','HANDYMAN','CARPOOLING'], $all_food_grocery_clone);
        $checkPermission = check_permission(1, $all_segments, true);
        if ($checkPermission['isRedirect']) {
            return $checkPermission['redirectBack'];
        }
    
        $request->validate([
            'reason'     => 'required',
            'segment_id' => 'required|array|min:1',
        ]);
    
        $cancelreason = CancelReason::where('merchant_id', $merchant_id)->findOrFail($id);
        $reasonTypeFor = $cancelreason->reason_type_for; // capture before any delete
    
        $group             = $this->getReasonSiblings($merchant_id, $cancelreason);
        $currentSegmentIds = $group->pluck('segment_id')->map(fn($v) => (int) $v)->all();
        $desiredSegmentIds = array_map('intval', array_values($request->segment_id));
        $reasonText        = $request->reason;
        $reasonStatus      = $cancelreason->reason_status; // preserve existing status
    
        DB::transaction(function () use (
            $merchant_id, $cancelreason, $group, $currentSegmentIds,
            $desiredSegmentIds, $reasonText, $reasonStatus
        ) {
            // 1) Remove segments unchecked in the form
            $toRemove = array_diff($currentSegmentIds, $desiredSegmentIds);
            foreach ($group as $row) {
                if (in_array((int) $row->segment_id, $toRemove, true)) {
                    LanguageCancelReason::where('cancel_reason_id', $row->id)->delete();
                    $row->delete();
                }
            }
    
            // 2) Update text on kept segments
            foreach ($group as $row) {
                if (in_array((int) $row->segment_id, $desiredSegmentIds, true)) {
                    $this->SaveLanguageCancel($merchant_id, $row->id, $reasonText);
                }
            }
    
            // 3) Add newly checked segments
            $toAdd = array_diff($desiredSegmentIds, $currentSegmentIds);
            foreach ($toAdd as $segId) {
                $new = CancelReason::create([
                    'merchant_id'     => $merchant_id,
                    'segment_id'      => $segId,
                    'reason_type'     => $cancelreason->reason_type,
                    'reason_type_for' => $cancelreason->reason_type_for,
                    'reason_status'   => $reasonStatus,
                ]);
                $this->SaveLanguageCancel($merchant_id, $new->id, $reasonText);
            }
        });
    
        return redirect()->route('cancelreason.index', ['reason_type_for' => $reasonTypeFor])
            ->withSuccess(trans("$string_file.saved_successfully"));
    }
    
    private function getReasonSiblings($merchant_id, CancelReason $reason)
    {
        return CancelReason::with(['LanguageSingle', 'LanguageAny'])
            ->where('merchant_id', $merchant_id)
            ->where('reason_type', $reason->reason_type)
            ->where('reason_type_for', $reason->reason_type_for)
            ->get()
            ->filter(fn($r) => $r->ReasonName === $reason->ReasonName)
            ->values();
    }

    public function Search(Request $request)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $query = CancelReason::where([['merchant_id', '=', $merchant_id]]);
        if ($request->reason) {
            $keyword = $request->reason;
            $query->WhereHas('LanguageSingle', function ($q) use ($keyword) {
                $q->where('reason', 'LIKE', "%$keyword%");
            });
        }
        if ($request->reason_for) {
            $query->where('reason_type', $request->reason_for);
        }
        $cancelreasons = $query->paginate(25);
        return view('merchant.cancelreason.index', compact('cancelreasons'));
    }

    public function destroy($id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
    
        $cancelReason = CancelReason::where('merchant_id', $merchant_id)->findOrFail($id);
    
        $group = $this->getReasonSiblings($merchant_id, $cancelReason);
    
        DB::transaction(function () use ($group) {
            foreach ($group as $row) {
                LanguageCancelReason::where('cancel_reason_id', $row->id)->delete();
                $row->delete();
            }
        });
    
        return $cancelReason;
    }
}

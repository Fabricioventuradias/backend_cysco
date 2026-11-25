@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">

        @if(session('reward'))
            <div class="alert dark alert-icon alert-info alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert">
                    <span>×</span>
                </button>
                <i class="icon wb-info"></i>
                {{ session('reward') }}
            </div>
        @endif

        <div class="panel panel-bordered">

            <header class="panel-heading">
                <div class="panel-actions">
                    <a href="{{ route('reward-points.index') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-reply" title="@lang('admin.message530')"></i>
                        </button>
                    </a>
                </div>
                <h3 class="panel-title">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    @lang("$string_file.edit") @lang("$string_file.reward_points")
                </h3>
            </header>

            <div class="panel-body container-fluid">
                <section id="validation">
                    <form method="POST" action="{{ route('merchant.rewardSystem.update', $reward_system->id) }}" class="steps-validation wizard-notification">
                        @csrf
                 

                        <fieldset>
                            <div class="row">

                                {{-- APPLICATION (disabled for display, hidden for submit) --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="text-capitalize">@lang("$string_file.app")</label>
                                        <select class="form-control" id="application" disabled>
                                            <option value="">@lang("$string_file.select")</option>
                                            <option value="1" {{ $reward_system->application == 1 ? 'selected' : '' }}>@lang("$string_file.user")</option>
                                            <option value="2" {{ $reward_system->application == 2 ? 'selected' : '' }}>@lang("$string_file.driver")</option>
                                        </select>
                                        {{-- Hidden so value always submits --}}
                                        <input type="hidden" name="application" value="{{ $reward_system->application }}">
                                        <span class="text-danger">{{ $errors->first('application') }}</span>
                                    </div>
                                </div>

                                {{-- COUNTRY (disabled for display, hidden for submit) --}}
                                <div class="col-md-4" id="country_div">
                                    <div class="form-group">
                                        <label class="text-capitalize">@lang("$string_file.select") @lang("$string_file.country")</label>
                                        <select class="form-control" id="country_display" disabled>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}" {{ $reward_system->country_id == $country->id ? 'selected' : '' }}>
                                                    {{ $country->CountryName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="country" value="{{ $reward_system->country_id }}">
                                        <span class="text-danger">{{ $errors->first('country') }}</span>
                                    </div>
                                </div>

                                {{-- AREA (disabled for display, hidden for submit) --}}
                                <div class="col-md-4" id="country_area_div">
                                    <div class="form-group">
                                        <label class="text-capitalize">@lang("$string_file.area")</label>
                                        <select class="form-control" id="country_area_display" disabled>
                                            @foreach($country_areas as $area)
                                                <option value="{{ $area->id }}" {{ $reward_system->country_area_id == $area->id ? 'selected' : '' }}>
                                                    {{ $area->CountryAreaName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="country_area" value="{{ $reward_system->country_area_id }}">
                                        <span class="text-danger">{{ $errors->first('country_area') }}</span>
                                    </div>
                                </div>

                                {{-- SEGMENTS --}}
                                <div class="col-md-4 custom-hidden" id="select_all_inputbox">
                                    <label>
                                        @lang("$string_file.segment")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-group">
                                        <select class="form-control select2" multiple name="segment_id[]" id="segment_id" required>
                                            @foreach($merchant_segments as $key => $value)
                                                <option value="{{ $key }}" {{ in_array($key, $selected_segments) ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- SELECT ALL CHECKBOX --}}
                                <div class="col-md-4 custom-hidden" id="select_all_checkbox">
                                    <label>@lang("$string_file.select") @lang("$string_file.all") @lang("$string_file.segment") <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        <input type="checkbox" class="form-check-label" id="select_all" style="width:20px;height:20px;">
                                    </div>
                                </div>

                            </div>

                            {{-- TRIP EXPENSES DIV (User only) --}}
                            <div id="trip_expenses_div" style="display:none">
                                <hr>
                                <div class="row">

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.according_to")</label>
                                            <select class="form-control" name="trips_select" id="trips_select" onchange="tripsCalc(this.value,'expenses-switch')">
                                                <option value="">--select---</option>
                                                <option value="2" {{ $reward_system->trip_expense_reward == 2 ? 'selected' : '' }}>@lang("$string_file.disable")</option>
                                                <option value="1" {{ $reward_system->trip_expense_reward == 1 ? 'selected' : '' }}>@lang("$string_file.trip_expenses")</option>
                                                <option value="3" {{ $reward_system->trip_expense_reward == 3 ? 'selected' : '' }}>@lang("$string_file.trip_expense_amount")</option>
                                                <option value="4" {{ $reward_system->trip_expense_reward == 4 ? 'selected' : '' }}>@lang("$string_file.no_of_trips")</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="trips_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.no_of_trips")</label>
                                            <input type="text" class="form-control expenses-switch" name="no_of_trips" value="{{ $reward_system->no_of_trips }}"/>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="type_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.trips_type")</label>
                                            <select class="form-control expenses-switch" name="trips_type">
                                                <option value="1" {{ $reward_system->trips_type == 1 ? 'selected' : '' }}>@lang("$string_file.one_time")</option>
                                                <option value="2" {{ $reward_system->trips_type == 2 ? 'selected' : '' }}>@lang("$string_file.recurring")</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="expense_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.trip_expense_amount")</label>
                                            <input type="text" class="form-control expenses-switch" name="trip_expense_amount" value="{{ $reward_system->expense_amount }}"/>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="point_trips" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.points_trip")</label>
                                            <input type="text" class="form-control expenses-switch" name="point_against_trips" value="{{ $reward_system->point_against_trips }}"/>
                                        </div>
                                    </div>

                                    <div class="col-md-3" id="per_point_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.per_point_amount")</label>
                                            <input type="number" min="0" step="0.01" max="1000000" class="form-control expenses-switch" name="commission_amount_per_point" value="{{ $reward_system->commission_amount_per_point }}"/>
                                            @if ($errors->has('per_point_amount'))
                                                <label class="text-danger">{{ $errors->first('per_point_amount') }}</label>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-3" id="expire_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.expire_in_days")</label>
                                            <input type="number" max="1000000" class="form-control expenses-switch" name="commission_expire_in_days" value="{{ $reward_system->commission_expire_in_days }}"/>
                                            @if ($errors->has('expenses_expire_in_days'))
                                                <label class="text-danger">{{ $errors->first('expenses_expire_in_days') }}</label>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>

                            {{-- COMMISSION PAID DIV (Driver only) --}}
                            <div id="commission_paid_div" style="display:none">
                                <hr>
                                <div class="row">

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.commission_paid")</label>
                                            <select class="form-control" name="commission_paid" id="commission_paid" onchange="commissionTripsCalc(this.value,'commission_paid-switch')">
                                                <option value="">--select--</option>
                                                <option value="2" {{ $reward_system->commission_paid_reward == 2 ? 'selected' : '' }}>@lang("$string_file.disable")</option>
                                                <option value="1" {{ $reward_system->commission_paid_reward == 1 ? 'selected' : '' }}>@lang("$string_file.trip_expenses") Commision</option>
                                                <option value="3" {{ $reward_system->commission_paid_reward == 3 ? 'selected' : '' }}>@lang("$string_file.trip_expense_amount")</option>
                                                <option value="4" {{ $reward_system->commission_paid_reward == 4 ? 'selected' : '' }}>@lang("$string_file.no_of_trips")</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="commission_trips_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.no_of_trips")</label>
                                            <input type="text" class="form-control commission_paid-switch" name="no_of_trips" value="{{ $reward_system->no_of_trips }}"/>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="commission_type_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.trips_type")</label>
                                            <select class="form-control commission_paid-switch" name="trips_type">
                                                <option value="1" {{ $reward_system->trips_type == 1 ? 'selected' : '' }}>@lang("$string_file.one_time")</option>
                                                <option value="2" {{ $reward_system->trips_type == 2 ? 'selected' : '' }}>@lang("$string_file.recurring")</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="commission_expense_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.trip_expense_amount")</label>
                                            <input type="text" class="form-control commission_paid-switch" name="trip_expense_amount" value="{{ $reward_system->expense_amount }}"/>
                                        </div>
                                    </div>

                                    <div class="col-md-2" id="commission_point_trips" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.points_trip")</label>
                                            <input type="text" class="form-control commission_paid-switch" name="point_against_trips" value="{{ $reward_system->point_against_trips }}"/>
                                        </div>
                                    </div>

                                    <div class="col-md-3" id="commission_per_point_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.per_point_amount")</label>
                                            <input type="number" min="0" step="0.01" max="1000000" class="form-control commission_paid-switch" name="commission_amount_per_point" value="{{ $reward_system->commission_amount_per_point }}"/>
                                            @if ($errors->has('commission_amount_per_point'))
                                                <label class="text-danger">{{ $errors->first('commission_amount_per_point') }}</label>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-3" id="commission_expire_div" style="display:none">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.expire_in_days")</label>
                                            <input type="number" max="1000000" class="form-control commission_paid-switch" name="commission_expire_in_days" value="{{ $reward_system->commission_expire_in_days }}"/>
                                            @if ($errors->has('commission_expire_in_days'))
                                                <label class="text-danger">{{ $errors->first('commission_expire_in_days') }}</label>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <hr>

                            {{-- STATUS --}}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="text-capitalize">@lang("$string_file.status")</label>
                                        <select class="form-control" name="status">
                                            <option value="1" {{ $reward_system->status == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="2" {{ $reward_system->status == 2 ? 'selected' : '' }}>Deactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </fieldset>

                        <div class="form-actions right" style="margin-bottom:3%">
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                            </button>
                        </div>

                    </form>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection


@section('js')
<script>

    // ── Helpers (mirrors create page) ─────────────────────────────────────────

    function disableTripExpenseFields() {
        $('#trip_expenses_div input, #trip_expenses_div select').prop('disabled', true);
    }

    function enableTripExpenseFields() {
        $('#trip_expenses_div input, #trip_expenses_div select').prop('disabled', false);
    }

    function disableCommissionFields() {
        $('#commission_paid_div input, #commission_paid_div select').prop('disabled', true);
    }

    function enableCommissionFields() {
        $('#commission_paid_div input, #commission_paid_div select').prop('disabled', false);
    }

    // ── Boot on page load ─────────────────────────────────────────────────────

    $(document).ready(function () {

        var app     = '{{ $reward_system->application }}';
        var tripVal = '{{ $reward_system->trip_expense_reward }}';
            var commissionVal = '{{ $reward_system->commission_paid_reward }}';

        // Start with both sections disabled (same as create page)
        disableTripExpenseFields();
        disableCommissionFields();

        if (app == 1) {
            $('#trip_expenses_div').show();
            $('#commission_paid_div').hide();
            $('#select_all_checkbox').show();
            $('#select_all_inputbox').show();

            enableTripExpenseFields();
            // commission stays disabled

            tripsCalc(tripVal, 'expenses-switch');

        } else if (app == 2) {
            $('#trip_expenses_div').hide();
            $('#commission_paid_div').show();
            $('#select_all_checkbox').show();
            $('#select_all_inputbox').show();

            enableCommissionFields();
            // trip expenses stays disabled

            commissionTripsCalc(commissionVal, 'commission_paid-switch');

        } else {
            $('#select_all_checkbox').hide();
            $('#select_all_inputbox').hide();
        }
    });

    // ── Application change (kept for consistency; field is disabled in edit) ──

    $(document).on('change', '#application', function () {
        var app = this.value;

        if (app == 1) {
            $('#trip_expenses_div').show();
            $('#commission_paid_div').hide();
            $('#select_all_checkbox').show();
            $('#select_all_inputbox').show();
            enableTripExpenseFields();
            disableCommissionFields();
        } else if (app == 2) {
            $('#trip_expenses_div').hide();
            $('#commission_paid_div').show();
            $('#select_all_checkbox').show();
            $('#select_all_inputbox').show();
            disableTripExpenseFields();
            enableCommissionFields();
        } else {
            $('#trip_expenses_div').hide();
            $('#commission_paid_div').hide();
            $('#select_all_checkbox').hide();
            $('#select_all_inputbox').hide();
            disableTripExpenseFields();
            disableCommissionFields();
        }
    });

    // ── Trip expenses (User) sub-field logic ──────────────────────────────────

    function tripsCalc(value, target) {
        $('#trips_div').hide();
        $('#type_div').hide();
        $('#expense_div').hide();
        $('#point_trips').hide();
        $('#per_point_div').hide();
        $('#expire_div').hide();

        if (value == 3) {
            $('#expense_div').show();
            $('#point_trips').show();
            $('#per_point_div').show();
            $('#expire_div').show();
        } else if (value == 4) {
            $('#trips_div').show();
            $('#type_div').show();
            $('#point_trips').show();
            $('#per_point_div').show();
            $('#expire_div').show();
        } else if (value == 1) {
            $('#per_point_div').show();
            $('#expire_div').show();
        }
        // value == 2 (disable) or empty: all stay hidden
    }

    // ── Commission paid (Driver) sub-field logic ──────────────────────────────

    function commissionTripsCalc(value, target) {
        $('#commission_trips_div').hide();
        $('#commission_type_div').hide();
        $('#commission_expense_div').hide();
        $('#commission_point_trips').hide();
        $('#commission_per_point_div').hide();
        $('#commission_expire_div').hide();

        if (value == 3) {
            $('#commission_expense_div').show();
            $('#commission_point_trips').show();
            $('#commission_per_point_div').show();
            $('#commission_expire_div').show();
        } else if (value == 4) {
            $('#commission_trips_div').show();
            $('#commission_type_div').show();
            $('#commission_point_trips').show();
            $('#commission_per_point_div').show();
            $('#commission_expire_div').show();
        } else if (value == 1) {
            $('#commission_per_point_div').show();
            $('#commission_expire_div').show();
        }
        // value == 2 (disable) or empty: all stay hidden
    }

    // ── Select all segments checkbox ──────────────────────────────────────────

    $("#select_all").click(function () {
        if ($(this).is(':checked')) {
            $('#segment_id option').prop('selected', true);
        } else {
            $('#segment_id option').prop('selected', false);
        }
        $('#segment_id').trigger('change');
    });

</script>
@endsection
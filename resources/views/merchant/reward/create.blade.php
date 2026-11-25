@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @if(session('reward'))
                <div class="alert dark alert-icon alert-info alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <i class="icon wb-info" aria-hidden="true"></i>{{ session('reward') }}
                </div>
            @endif
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('reward-points.index') }}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang('admin.message530')"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        @lang("$string_file.add") @lang("$string_file.reward_points")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification" action="{{ route('merchant.rewardSystem.store') }}">
                            @csrf
                            <fieldset>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.app")</label>
                                            <select class="form-control" name="application" id="application">
                                                <option value=""> @lang("$string_file.select") </option>
                                                <option value="1">@lang("$string_file.user")</option>
                                                <option value="2">@lang("$string_file.driver")</option>
                                            </select>
                                            <span class="text-danger">{{ $errors->first('application') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 custom-hidden" id="country_div">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.select") @lang("$string_file.country")</label>
                                            <select class="form-control" name="country">
                                                <option value=""> @lang("$string_file.select") </option>
                                                @foreach($countries as $country)
                                                    <option value="{{$country->id}}"
                                                            {{(old('country') == $country->id) ? ' selected' : ''}}>
                                                        {{ $country->CountryName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger">{{ $errors->first('country') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 custom-hidden" id="country_area_div">
                                        <div class="form-group">
                                            <label class="text-capitalize">@lang("$string_file.area")</label>
                                            <select class="form-control" name="country_area">
                                                <option value=""> @lang("$string_file.select") </option>
                                                @foreach($country_areas as $area)
                                                    <option value="{{$area->id}}"
                                                            {{(old('country_area') == $area->id) ? ' selected' : ''}}>
                                                        {{ $area->CountryAreaName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-danger">{{ $errors->first('country_area') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 custom-hidden" id="select_all_inputbox">
                                        <label>
                                            @lang("$string_file.segment")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="form-group">
                                            <select class="form-control select2"
                                                    multiple
                                                    name="segment_id[]"
                                                    id="segment_id"
                                                    required>
                                                @foreach($merchant_segments as $key => $value)
                                                    <option value="{{$key}}">{{$value}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 custom-hidden" id="select_all_checkbox">
                                        <label>@lang("$string_file.select") @lang("$string_file.all") @lang("$string_file.segment") <span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <input type="checkbox" class="form-check-label" id="select_all" style="width: 20px; height:20px;">
                                        </div>
                                    </div>
                                </div>

                                {{-- Trip Expenses Div (User only) --}}
                                <div id="trip_expenses_div" style="display:none">
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.according_to")</label>
                                                <select class="form-control" name="trips_select" id="trips_select" onchange="tripsCalc(this.value,'expenses-switch')">
                                                    <option value="">--select---</option>
                                                    <option value="2"> @lang("$string_file.disable") </option>
                                                    <option value="1"> @lang("$string_file.trip_expenses") </option>
                                                    <option value="3"> @lang("$string_file.trip_expense_amount") </option>
                                                    <option value="4"> @lang("$string_file.no_of_trips") </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="trips_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.no_of_trips")</label>
                                                <input type="text" class="form-control expenses-switch" name="no_of_trips" value="{{old('no_of_trips')}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="type_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.trips_type")</label>
                                                <select class="form-control expenses-switch" name="trips_type">
                                                    <option value="1"> @lang("$string_file.one_time") </option>
                                                    <option value="2"> @lang("$string_file.recurring") </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="expense_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.trip_expense_amount")</label>
                                                <input type="text" class="form-control expenses-switch" name="trip_expense_amount" value="{{old('trip_expense_amount')}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="point_trips" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.points_trip")</label>
                                                <input type="text" class="form-control expenses-switch" name="point_against_trips" value="{{old('point_against_trips')}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="per_point_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.per_point_amount")</label>
                                                <input type="number" min="0" step="0.01" max="1000000" class="form-control expenses-switch" name="per_point_amount" value="{{old('per_point_amount')}}"/>
                                                @if ($errors->has('per_point_amount'))
                                                    <label class="text-danger">{{ $errors->first('per_point_amount') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="expire_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.expire_in_days")</label>
                                                <input type="number" max="1000000" class="form-control expenses-switch" name="expenses_expire_in_days" value="{{old('expenses_expire_in_days')}}"/>
                                                @if ($errors->has('expenses_expire_in_days'))
                                                    <label class="text-danger">{{ $errors->first('expenses_expire_in_days') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Commission Paid Div (Driver only) --}}
                                <div id="commission_paid_div" style="display:none">
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.commission_paid")</label>
                                                <select class="form-control" name="commission_paid" id="commission_paid" onchange="commissionTripsCalc(this.value,'commission_paid-switch')">
                                                    <option value="">--select--</option>
                                                    <option value="2"> @lang("$string_file.disable") </option>
                                                    <option value="1"> @lang("$string_file.trip_expenses") Commision</option>
                                                    <option value="3"> @lang("$string_file.trip_expense_amount") </option>
                                                    <option value="4"> @lang("$string_file.no_of_trips") </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="commission_trips_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.no_of_trips")</label>
                                                <input type="text" class="form-control commission_paid-switch" name="no_of_trips" value="{{old('no_of_trips')}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="commission_type_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.trips_type")</label>
                                                <select class="form-control commission_paid-switch" name="trips_type">
                                                    <option value="1"> @lang("$string_file.one_time") </option>
                                                    <option value="2"> @lang("$string_file.recurring") </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="commission_expense_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.trip_expense_amount")</label>
                                                <input type="text" class="form-control commission_paid-switch" name="trip_expense_amount" value="{{old('trip_expense_amount')}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="commission_point_trips" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.points_trip")</label>
                                                <input type="text" class="form-control commission_paid-switch" name="point_against_trips" value="{{old('point_against_trips')}}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="commission_per_point_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.per_point_amount")</label>
                                                <input type="number" min="0" step="0.01" max="1000000" class="form-control commission_paid-switch" name="commission_amount_per_point" value="{{old('commission_amount_per_point')}}"/>
                                                @if ($errors->has('commission_amount_per_point'))
                                                    <label class="text-danger">{{ $errors->first('commission_amount_per_point') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-3" id="commission_expire_div" style="display:none">
                                            <div class="form-group">
                                                <label class="text-capitalize">@lang("$string_file.expire_in_days")</label>
                                                <input type="number" max="1000000" class="form-control commission_paid-switch" name="commission_expire_in_days" value="{{old('commission_expire_in_days')}}"/>
                                                @if ($errors->has('commission_expire_in_days'))
                                                    <label class="text-danger">{{ $errors->first('commission_expire_in_days') }}</label>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                            </fieldset>
                            <div class="form-actions right" style="margin-bottom: 3%">
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
        $(document).ready(function () {
            // Hide everything on load
            $('#country_div').hide();
            $('#country_area_div').hide();
            $('#trip_expenses_div').hide();
            $('#commission_paid_div').hide();
            $('#select_all_checkbox').hide();
            $('#select_all_inputbox').hide();

            // Disable all trip_expenses_div fields on load so they don't submit
            disableTripExpenseFields();

            // Disable all commission_paid_div fields on load so they don't submit
            disableCommissionFields();
        });

        // Disable all fields inside trip_expenses_div
        function disableTripExpenseFields() {
            $('#trip_expenses_div input, #trip_expenses_div select').prop('disabled', true);
        }

        // Enable all fields inside trip_expenses_div
        function enableTripExpenseFields() {
            $('#trip_expenses_div input, #trip_expenses_div select').prop('disabled', false);
        }

        // Disable all fields inside commission_paid_div
        function disableCommissionFields() {
            $('#commission_paid_div input, #commission_paid_div select').prop('disabled', true);
        }

        // Enable all fields inside commission_paid_div
        function enableCommissionFields() {
            $('#commission_paid_div input, #commission_paid_div select').prop('disabled', false);
        }

        // Application change handler
        $(document).on('change', '#application', function () {
            var app = this.value;

            if (app == 1) {
                // User: show trip expenses + segment, hide commission
                $('#country_div').show();
                $('#country_area_div').show();
                $('#trip_expenses_div').show();
                $('#commission_paid_div').hide();
                $('#select_all_checkbox').show();
                $('#select_all_inputbox').show();

                // Enable trip fields, disable commission fields
                enableTripExpenseFields();
                disableCommissionFields();

            } else if (app == 2) {
                // Driver: show commission + segment, hide trip expenses
                $('#country_div').show();
                $('#country_area_div').show();
                $('#trip_expenses_div').hide();
                $('#commission_paid_div').show();
                $('#select_all_checkbox').show();
                $('#select_all_inputbox').show();

                // Enable commission fields, disable trip fields
                disableTripExpenseFields();
                enableCommissionFields();

            } else {
                // Nothing selected: hide both, disable both
                $('#country_div').hide();
                $('#country_area_div').hide();
                $('#trip_expenses_div').hide();
                $('#commission_paid_div').hide();
                $('#select_all_checkbox').hide();
                $('#select_all_inputbox').hide();

                disableTripExpenseFields();
                disableCommissionFields();
            }
        });

        // Trip expenses (User) logic
        function tripsCalc(value, target) {
            // Hide all sub-fields first
            $('#trips_div').hide();
            $('#type_div').hide();
            $('#expense_div').hide();
            $('#point_trips').hide();
            $('#per_point_div').hide();
            $('#expire_div').hide();

            if (value == 3) {
                // Trip Expense Amount
                $('#expense_div').show();
                $('#point_trips').show();
                $('#per_point_div').show();
                $('#expire_div').show();
            } else if (value == 4) {
                // No of Trips
                $('#trips_div').show();
                $('#type_div').show();
                $('#point_trips').show();
                $('#per_point_div').show();
                $('#expire_div').show();
            } else if (value == 1) {
                // Trip Expenses
                $('#per_point_div').show();
                $('#expire_div').show();
            }
            // value == 2 (disable) or empty: all stay hidden
        }

        // Commission paid (Driver) logic
        function commissionTripsCalc(value, target) {
            // Hide all sub-fields first
            $('#commission_trips_div').hide();
            $('#commission_type_div').hide();
            $('#commission_expense_div').hide();
            $('#commission_point_trips').hide();
            $('#commission_per_point_div').hide();
            $('#commission_expire_div').hide();

            if (value == 3) {
                // Trip Expense Amount
                $('#commission_expense_div').show();
                $('#commission_point_trips').show();
                $('#commission_per_point_div').show();
                $('#commission_expire_div').show();
            } else if (value == 4) {
                // No of Trips
                $('#commission_trips_div').show();
                $('#commission_type_div').show();
                $('#commission_point_trips').show();
                $('#commission_per_point_div').show();
                $('#commission_expire_div').show();
            } else if (value == 1) {
                // Trip Expenses
                $('#commission_per_point_div').show();
                $('#commission_expire_div').show();
            }
            // value == 2 (disable) or empty: all stay hidden
        }

        // Select all segments checkbox
        $("#select_all").click(function () {
            if ($("#select_all").is(':checked')) {
                $('#segment_id option').prop('selected', true);
            } else {
                $('#segment_id option').prop('selected', false);
            }
            $('#segment_id').trigger('change');
        });
    </script>
@endsection
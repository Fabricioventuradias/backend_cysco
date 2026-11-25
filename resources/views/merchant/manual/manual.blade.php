@extends('merchant.layouts.main')
@section('content')
    @php
        /* ---------------------------------------------------------------
         * MULTI-DESTINATION (multi-stop) SETTINGS — driven by merchant config.
         * If `$bookingConfig` in your app is already the same object as
         * $merchant->BookingConfiguration, you can use $bookingConfig->multi_destination
         * / $bookingConfig->count_multi_destination instead — functionally identical.
         * --------------------------------------------------------------- */
        $multiDestinationEnabled  = (($merchant->BookingConfiguration->multi_destination ?? 0) == 1);
        $maxMultiDestinationCount = $merchant->BookingConfiguration->count_multi_destination ?? 0;
    @endphp
    <div class="page">
        @include('loader')
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        @if($baseConfig->corporate_admin == 1)
                            <a href="{{route('merchant.corporate.manualdispach')}}">
                                <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                       type="button">
                                    Corporate Manual Dispatch
                                </button>
                            </a>
                        @endif
                    </div>
                    <h3 class="panel-title"><i class="fa-truck" aria-hidden="true"></i> @lang("$string_file.manual_dispatch")
                    </h3>
                </div>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" enctype="multipart/form-data" id="manualDispatchForm"
                          action="{{ route('merchant.book.manual.dispach') }}" onsubmit="return validation()">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="wb-user"></i> @lang("$string_file.user_details")</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <select class="form-control form-control" id="manual_user_type"
                                                    name="manual_user_type" disabled="true">
                                                <option value="2" selected>@lang("$string_file.registered_user")</option>
                                                <option value="1">@lang("$string_file.new_user")</option>
                                                @if(Auth::user('merchant')->can('view_corporate') && $baseConfig->corporate_admin == 1)
                                                    <option value="3">@lang("$string_file.corporate_user")
                                                    </option>
                                                @endif
                                            </select>
                                        </div>
                                        <input type="hidden" id="user_id" name="user_id">
                                        @if ($errors->has('user_id'))
                                            <label class="text-danger">{{ $errors->first('user_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <select class="form-control form-control" name="country"
                                                    id="country" onchange="changeCountryCanter(this)">
                                                <option value="">@lang("$string_file.country_code")
                                                </option>
                                                @foreach($countries  as $index => $country)
                                                    <option data-min="{{ $country->minNumPhone }}"
                                                            data-max="{{ $country->maxNumPhone }}"
                                                            data-country-code="{{ $country->country_code }}"
                                                            value="{{ $country->id }}"
                                                            data-phone="{{$country->phonecode}}" {{ $index === 0 ? 'selected' : '' }}>({{ $country->phonecode }}) {{ isset($country->LanguageCountrySingle->name) ? $country->LanguageCountrySingle->name : $country->LanguageCountryAny->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="user_phone" name="user_phone"
                                                   placeholder="@lang("$string_file.phone")" required/>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group pull-right" id="check_user_details_div">
                                            <button type="button" class="btn btn-primary"
                                                    id="check_user_details">@lang("$string_file.search")</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 custom-hidden" id="first_name_new_div">
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="new_user_first_name"
                                                   name="new_user_first_name"
                                                   placeholder="@lang("$string_file.first_name")" autocomplete="off"/>
                                        </div>
                                    </div>
                                    <div class="col-md-6 custom-hidden" id="last_name_new_div">
                                        <div class="form-group">
                                            <input type="text" class="form-control" id="new_user_last_name"
                                                   name="new_user_last_name"
                                                   placeholder="@lang("$string_file.last_name")" autocomplete="off"/>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 custom-hidden" id="corporate_div">
                                        <div class="form-group">
                                            <select class="form-control" name="corporate_id"
                                                    id="corporate_id">
                                                <option value="">--@lang("$string_file.select_corporate")--</option>
                                                @foreach($corporates as $corporate)
                                                    <option value="{{ $corporate->id }}">{{ $corporate->corporate_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @if(isset($bookingConfig->email_option_signup) && $bookingConfig->email_option_signup == 1)
                                        <div class="col-md-6 custom-hidden" id="email_new_div">
                                            <div class="form-group">
                                                <input type="text" class="form-control" id="new_user_email"
                                                       name="new_user_email"
                                                       placeholder="@lang("$string_file.email")" autocomplete="off"/>
                                            </div>
                                        </div>
                                    @endif
                                    @if($config->gender == 1)
                                        <div class="col-md-6 custom-hidden" id="gender_new_div">
                                            <div class="form-group">
                                                <select class="form-control form-control" name="new_user_gender"
                                                        id="new_user_gender">
                                                    <option value="1">@lang("$string_file.male")</option>
                                                    <option value="2">@lang("$string_file.female")</option>
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-md-3 custom-hidden" id="new_user_details_div">
                                        <div class="form-group pull-right">
                                            <button type="button" class="btn btn-primary"
                                                    id="new_user_details">@lang("$string_file.register_user")</button>
                                        </div>
                                    </div>
                                    <div class="col-md-3 custom-hidden" id="full_details_div">
                                        <div class="form-group pull-right">
                                            <a target="_blank" id="full_details" class="btn btn-primary" disabled>
                                                <i class="icon wb-user-circle" aria-hidden="true"></i>
                                                @lang("$string_file.full_details")
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <h5><i class="fa fa-dollar"></i> @lang("$string_file.segment")</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <select class="form-control" name="segment_id" id="segment">
                                                <option value="">@lang("$string_file.select")</option>
                                                @foreach($segments as $index=>$segment)
                                                    <option value="{{$segment->id}}" {{$index == 0 ? 'selected' : ""}}>{{$segment->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <h5><i class="fa fa-dollar"></i> @lang("$string_file.price_for_ride")</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <select class="form-control" name="price_for_ride" id="price_for_ride">
                                                <option value="">@lang("$string_file.select")</option>
                                                <option value="1" selected>@lang("$string_file.from_price_card")</option>
                                                <option value="2">@lang("$string_file.fix_amount")</option>
                                                <option value="3">@lang("$string_file.maximum_amount")</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 custom-hidden" id="price_ride_value_div">
                                        <div class="form-group">
                                            {{--                                            <input class="form-control" type="text" id="price_for_ride_value" name="price_for_ride_value" placeholder="@lang("$string_file.enter_amount")">--}}
                                            <input class="form-control" type="text" id="price_for_ride_value" name="price_for_ride_value" placeholder="@lang("$string_file.enter_amount")" onkeyup="checkZero(this)">
                                        </div>
                                    </div>
                                </div>
                                <h5><i class="wb-book"></i> @lang("$string_file.ride_details")</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <!--<input type="text" class="form-control" id="pickup_location" name="pickup_location"-->
                                            <!--       placeholder="@lang("$string_file.pickup_location")" autocomplete="off" onkeyup="resetPromoCode();getPickupLoction();" />-->
                                            <select class="form-control" id="pickup_location" name="pickup_location" style="width: 100%" disabled>
                                            </select>
                                            <input type="hidden" id="pickup_latitude" name="pickup_latitude" value="">
                                            <input type="hidden" id="pickup_longitude" name="pickup_longitude" value="">
                                            <input type="hidden" id="drop_latitude" name="drop_latitude" value="">
                                            <input type="hidden" id="drop_longitude" name="drop_longitude" value="">
                                            <!-- NEW: full address text for the main Drop location (the <select>'s own
                                                 .value only holds the short main_text, same issue as the stop fields). -->
                                            <input type="hidden" id="drop_location_text" value="">
                                            <!-- NEW: when extra stops exist, this carries the combined
                                                 [stops..., main drop] JSON array. Its "name" attribute is
                                                 set dynamically in buildWaypointsJson() — only ONE of this
                                                 field or the <select id="drop_location"> above ever carries
                                                 name="drop_location" at submit time, so there's no ambiguity
                                                 about which value wins. For a plain single-destination
                                                 booking (no stops), the <select> keeps that name and submits
                                                 its normal value, unchanged from before. -->
                                            <input type="hidden" id="drop_location_full_json" value="">
                                            <input type="hidden" id="distance" name="distance" value="">
                                            <input type="hidden" id="estimate_distance" name="estimate_distance" value="">
                                            <input type="hidden" id="ride_time" name="ride_time" value="">
                                            <input type="hidden" id="estimate_time" name="estimate_time" value="">
                                            <input type="hidden" id="estimate_fare" name="estimate_fare" value="">
                                            <input type="hidden" id="manual_area" name="manual_area" value="">
                                            <input type="hidden" id="selected_pickup_area" name="selected_pickup_area" value="">
                                            <input type="hidden" id="is_geofence" name="is_geofence" value="">
                                            @if ($errors->has('pickup_location'))
                                                <label class="text-danger">{{ $errors->first('pickup_location') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="input-group form-group after-add-more" id="drop_location_div">
                                            <!--<input type="text" class="form-control"-->
                                            <!--       id="drop_location" name="drop_location"-->
                                            <!--       placeholder="@lang("$string_file.drop_off_location")" autocomplete="off" onkeyup="resetPromoCode();getDropLoction();">-->

                                            <select class="form-control" id="drop_location" name="drop_location" style="width: 100%" disabled>
                                            </select>

                                            <div class="input-group-btn">
                                                <button style="display: none" class="btn btn-success add-more" type="button" id="add_loc">
                                                    <i class="wb-plus"></i>@lang("$string_file.add")</button>
                                            </div>
                                            @if ($errors->has('drop_location'))
                                                <label class="text-danger">{{ $errors->first('drop_location') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div id="multiple_location"></div>
                                        <label class="text-primary  pull-right mr-10 text-danger"
                                               id="distance_and_time"></label><br>
                                        <label class="text-info pull-right mr-2 text-danger"
                                               id="estimate_fare_ride"></label><br>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-control-label">@lang("$string_file.ride_type")</label>
                                            <span class="text-danger">*</span>
                                            <div>
                                                <div class="radio-custom radio-default radio-inline">
                                                    <input type="radio" id="radio1" name="booking_type" onclick="RideType(this.value)" value="1" checked/>
                                                    <label for="radio1">@lang("$string_file.ride_now")</label>
                                                </div>
                                                <div class="radio-custom radio-default radio-inline">
                                                    <input type="radio" id="radio2" name="booking_type" onclick="RideType(this.value)" value="2" />
                                                    <label for="radio2">@lang("$string_file.ride")  @lang("$string_file.later") </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 custom-hidden" id="start-div">
                                        <div class="form-group">
                                            <label class="form-control-label" for="datepicker">@lang("$string_file.start")  @lang("$string_file.date")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="icon wb-calendar" aria-hidden="true"></i></span>
                                                </div>
                                                <input type="text" class="form-control customDatePicker1" id="datepicker" name="date"
                                                       placeholder="@lang("$string_file.start")  @lang("$string_file.date") " autocomplete="off" >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 custom-hidden" id="end-div">
                                        <div class="form-group">
                                            <label class="form-control-label" for="inputtime">@lang("$string_file.start")  @lang("$string_file.time")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="wb-time" aria-hidden="true"></i></span>
                                                </div>
                                                <input type="text" class="timepicker form-control" data-plugin="clockpicker" data-autoclose="true"
                                                       id="time" name="time" placeholder="@lang("$string_file.start")  @lang("$string_file.time") " autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 custom-hidden" id="retrun_date_div">
                                        <div class="form-group">
                                            <label class="form-control-label" for="inputdatepicker">@lang("$string_file.return_date")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="icon wb-calendar" aria-hidden="true"></i></span>
                                                </div>
                                                <input type="text" class="form-control customDatePicker1" id="datepicker" name="retrun_date"
                                                       placeholder="" autocomplete="off" >
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 custom-hidden" id="retrun_time_div">
                                        <div class="form-group">
                                            <label class="form-control-label" for="time">@lang("$string_file.return_time")
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                            <span class="input-group-text">
                                              <i class="wb-time" aria-hidden="true"></i>
                                            </span>
                                                </div>
                                                <input type="text" class="timepicker form-control" data-plugin="clockpicker" data-autoclose="true"
                                                       id="time" name="retrun_time" placeholder="@lang("$string_file.return_time") " autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"
                                         id="vehicle_type_id">
                                        <div class="form-group">
                                            <select class="form-control form-control" name="vehicle_type"
                                                    id="vehicle_type">
                                                <option value="">--@lang("$string_file.vehicle_type") --</option>
                                            </select>
                                            @if ($errors->has('vehicle_type'))
                                                <label class="text-danger">{{ $errors->first('vehicle_type') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <select class="form-control form-control" name="service"
                                                    id="service" onchange="getDistance()">
                                                <option value="">--@lang("$string_file.service_type") --
                                                </option>
                                            </select>
                                            @if ($errors->has('service'))
                                                <label class="text-danger">{{ $errors->first('service') }}</label>
                                            @endif
                                        </div>
                                        {!! Form::hidden('additional_support',null,['class'=>'form-control','id'=>'additional_support']) !!}
                                    </div>
                                    <div class="col-md-6 custom-hidden" id="outstation_type">
                                        <div class="form-group">
                                            <select class="form-control form-control" name="outstation_type_val"
                                                    id="outstation_type_val">
                                                <option value="">@lang("$string_file.fare_type") </option>
                                                <option value="1">@lang("$string_file.round_trip_only")</option>
                                                <option value="2">@lang("$string_file.one_way_round_trip")</option>
                                            </select>
                                            @if ($errors->has('outstation_type_val'))
                                                <label class="text-danger">{{ $errors->first('outstation_type_val') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-6 custom-hidden" id="package_id">
                                        <div class="form-group">
                                            <select class="form-control form-control" name="package"
                                                    id="package">
                                                <option value="">--@lang("$string_file.select_package")--</option>
                                            </select>
                                            @if ($errors->has('package'))
                                                <label class="text-danger">{{ $errors->first('package') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <select class="form-control form-control" name="payment_method_id"
                                                    id="payment_method_id">
                                                <option value="">--@lang("$string_file.payment_method")--</option>
                                                <option value="1">@lang("$string_file.cash")</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <select class="form-control form-control" name="promo_code"
                                                    id="promo_code">
                                                <option value="">--@lang("$string_file.promo_code")--</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-control-label" for="driver_marker">@lang("$string_file.driver_availability"):
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control form-control-sm" name="type" id="driver_marker">
                                                <option value="1">@lang("$string_file.all")</option>
                                                <option value="2">@lang("$string_file.available")</option>
                                                <option value="3">@lang("$string_file.enroute_pickup")</option>
                                                <option value="4">@lang("$string_file.reached_pickup")</option>
                                                <option value="5">@lang("$string_file.journey_started")</option>
                                                <option value="6">@lang("$string_file.offline")</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div id="map" style="width: 100%;height: 550px;"></div>
                                    </div>
                                </div>
                                @if($bookingConfig->additional_note_for_admin == 1)
                                    <br><br>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea class="form-control" id="placeTextarea" rows="3" name="note" placeholder="@lang("$string_file.additional_notes")"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <input type="hidden" name="distance_unit" id="distance_unit">
                        <input type="hidden" name="multi_destination" id="multi_destination" value="{{ $multiDestinationEnabled ? 1 : 0 }}">
                        <input type="hidden" name="isoCode" id="isocode">
                        <input type="hidden" name="max__multi_count" id="max__multi_count" value="{{ $maxMultiDestinationCount }}">
                        <input type="hidden" name="old_eta" id="old_eta">
                        <input type="hidden" name="promo_id" id="promo_id">
                        <input type="hidden" name="price_card_id" id="price_card_id">
                        <!-- NEW: JSON array of extra stops, built in buildWaypointsJson() right before submit.
                             Only populated when the user actually adds one or more stops via "Add";
                             stays empty for a normal single-destination booking. -->
                        <input type="hidden" name="waypoints" id="waypoints_json" value="">
                        <!-- NEW: total number of drop locations in this booking. Starts at 1
                             (the main Drop location) and goes up by 1 for every extra stop added
                             via "Add" — kept in sync in the add-more/remove handlers below, and
                             finalized in buildWaypointsJson() right before submit. -->
                        <input type="hidden" name="total_drop_location" id="total_drop_location" value="1">
                        <div id="delivery_details_div" style="display:none;">
                            <h5 class="form-section col-md-12" style="color: black;"><i class="fa fa-taxi"></i> @lang("$string_file.delivery") @lang("$string_file.details")</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <h5>@lang("$string_file.products")<span class="text-danger">*</span></h5><hr>
                                    @foreach($merchant->DeliveryProduct as $product)
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="checkbox" name="delivery_product[{{$product->id}}]" id="delivery_product"> {{$product->ProductName}}
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" class="form-control" name="delivery_product_data[{{$product->id}}]">
                                                </div>
                                                <div class="col-md-2">
                                                    {{$product->WeightUnit->WeightUnitName}}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-md-4">
                                    <h5>@lang("$string_file.product") @lang("$string_file.images")<span class="text-danger">*</span></h5><hr>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input type="file" name="product_image_one" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input type="file" name="product_image_two" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h5>@lang("$string_file.receiver") @lang("$string_file.details")<span class="text-danger">*</span></h5><hr>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input type="text" name="receiver_name" class="form-control" placeholder="@lang("$string_file.receiver") @lang("$string_file.name")">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <input type="text" name="receiver_phone" class="form-control" placeholder="@lang("$string_file.receiver") @lang("$string_file.phone")">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h5 class="form-section col-md-12" style="color: black;"><i class="fa fa-taxi"></i> @lang("$string_file.assign_ride")</h5>
                        <hr>
                        <div class="row">
                            @if($config->gender == 1)
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-control-label" for="driver_gender">@lang("$string_file.gender")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control form-control" name="driver_gender"
                                                id="driver_gender">
                                            <option value="1">@lang("$string_file.male")</option>
                                            <option value="2">@lang("$string_file.female")</option>
                                        </select>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div id="manual_driver_selection">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label class="form-control-label"></label>
                                    <div>
                                        <div class="radio-custom radio-default radio-inline">
                                            <input type="radio" id="all_driver_booking" name="driver_request" value="1" checked />
                                            <label for="all_driver_booking"> @lang("$string_file.send_booking_to_all_drivers")</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="form-control-label" for="ride_radius_driver">@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="ride_radius_driver" name="ride_radius" required="" value="0"
                                           placeholder="@lang("$string_file.enter") @lang("$string_file.number")" min="0">
                                    <label class="text-danger"><b id="number_of_drivers">0</b> @lang("$string_file.drivers") (@lang("$string_file.online") @lang("$string_file.and_symbol") @lang("$string_file.free") )</label>
                                </div>
                            </div>
                            <div class="row">
                                <h4 style="text-align:center; margin:10px auto; font-weight:bold; width:100%;">@lang("$string_file.or")</h4>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-control-label"></label>
                                        <div>
                                            <div class="radio-custom radio-default radio-inline">
                                                <input type="radio" id="manually_driver" name="driver_request" value="3" data-plugin="icheck" />
                                                <label for="manually_driver"> @lang("$string_file.select_driver_manually")</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="ride_radius_manual_driver">@lang("$string_file.enter_radius") ( @lang("$string_file.in_km") )</label>
                                        <input type="number" class="c-select form-control"
                                               id="ride_radius_manual_driver"
                                               name="ride_radius_manual_driver" value="0" min="0" disabled>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="driver_id">@lang("$string_file.driver")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control form-control select2" name="driver_id"
                                                id="driver_id" >
                                            <option value="">--@lang("$string_file.driver")--</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        @if($merchant->id == 976)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="checkbox" id="is_fake_booking" name="is_fake_booking" />
                                        <label for="is_fake_booking_label">Office Testing</label>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="form-actions float-right" style="margin-bottom: 1%">
                            <button type="button" id="resetForm" class="btn btn-warning mr-1">
                                <i class="fas fa-times"></i> @lang("$string_file.reset")
                            </button>
                            <button type="submit" id="manualDispatch" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.book")
                            </button>
                        </div>
                    </form>
                    <div class="dynamic_lat_long">
                        <input type="hidden" id="q_value" value="">
                    </div>
                    <div class="copy custom-hidden">
                        <div class="input-group" style="margin-top:10px; margin-bottom:10px">
                            <!-- NEW: this is now a <select> so it can be enhanced with the same
                                 Select2 + search-places autocomplete used by Pickup/Drop.
                                 Select2 is attached to it in the .add-more click handler below,
                                 right after its id gets renamed to drop_loc_<N>. -->
                            <select class="form-control" name="multiple_destination[]" id="drop_loc" style="width: 100%">
                            </select>
                            <input type="hidden" id="drop_loc_latitude" name="multiple_destination_lat" value="">
                            <input type="hidden" id="drop_loc_longitude" name="multiple_destination_lng" value="">
                            <!-- NEW: full address text for this stop (Select2's underlying <select>
                                 .value only holds the short main_text, not the full description). -->
                            <input type="hidden" id="drop_loc_text" name="multiple_destination_text" value="">
                            <div class="input-group-btn">
                                <button class="btn btn-danger remove" type="button"><i
                                            class="glyphicon glyphicon-remove"></i> @lang("$string_file.remove")
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@section('js')

    @php
        $key = get_merchant_google_key(NULL,'admin_backend');
    @endphp

    <script src="https://maps.googleapis.com/maps/api/js?key=<?= $key ?>&libraries=geometry"></script>
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        // $('#manualDispatch').on('click', function () {
        //     $('#myLoader').removeClass('d-none');
        //     $('#myLoader').addClass('d-flex');
        // });

        var restric_config = "{{$config->restrict_country_wise_searching}}";
        var options = {};
        // console.log(options);
        $('#country').on('change',function () {
            options = getOption();
        });
        function getOption()
        {
            var options = {};
            var  country_val = $("#country option:selected").val();
            // console.log(country_val);
            if(restric_config == 1 && country_val !="")
            {
                var  country_code = $("#country option:selected").attr("data-country-code");
                options = {
                    strictBounds: false,
                    fields: ["address_components", "geometry", "icon", "name"],
                    // types: ['(regions)'],
                    componentRestrictions: {country: country_code}
                };
            }
            return options
        }

        $('#time').on('change',function () {
            // console.log($('#time').val());
            var hours = parseInt($('#time').val().split(':')[0], 10) - new Date().getHours();
            var minutes = parseInt($('#time').val().split(':')[1], 10) - new Date().getMinutes();

            var current_date_time = new Date();
            var current_date = current_date_time.getFullYear()+'-'+String(current_date_time.getMonth() + 1).padStart(2, '0')+'-'+String(current_date_time.getDate()).padStart(2, '0');
            var selected = $('#datepicker').val();
            // console.log("Current Date: "+current_date);
            // console.log("Selected Date: "+selected);

            var total_time_diff =  minutes + (hours * 60);
            // console.log(total_time_diff);
            if((selected <= current_date) && (total_time_diff < 0)){
                sweetalert("@lang("$string_file.past_time_error")");
                $("#time").val('');
                return false;
            }
            // console.log($('#time').val());
            // console.log(new Date().getHours());
            // console.log(new Date().getMinutes());
        });

        $("input[name=date]").on('change',function ()
        {
            var selected = $('input[name=date]').val();
            // console.log("Selected Date Changed: "+selected);
            var hours = parseInt($('#time').val().split(':')[0], 10) - new Date().getHours();
            var minutes = parseInt($('#time').val().split(':')[1], 10) - new Date().getMinutes();
            var current_date_time = new Date();
            var current_date = current_date_time.getFullYear()+'-'+String(current_date_time.getMonth() + 1).padStart(2, '0')+'-'+current_date_time.getDate();
            var selected = $('#datepicker').val();
            // console.log("Current Date: "+current_date);
            // console.log("Selected Date: "+selected);
            if((selected <= current_date) && (hours < 0 || minutes < 0))
            {
                sweetalert("@lang("$string_file.past_time_error")");
                $("#time").val('');
                return false;
            }
            // console.log($('#time').val());
            // console.log(new Date().getHours());
            // console.log(new Date().getMinutes());
        });

        function sweetalert(msg) {
            swal({
                title: "@lang("$string_file.error")",
                text: msg,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            });
        }

        let drivermarker;
        let map;
        let driverMarkers = [];
        let infowindow;
        let driverLocations;
        // NEW: markers for pickup / stops / drop, kept separate from driverMarkers so
        // refreshing the route never wipes the live driver pins, and vice versa.
        let stopMarkers = [];

        function initialize() {
            var center = new google.maps.LatLng(20.5937, 78.9629);
            var mapOptions = {
                zoom: 2,
                center: center,
                mapTypeId: google.maps.MapTypeId.TERRAIN
            };
            map = new google.maps.Map(document.getElementById('map'), mapOptions);
        }

        function drawRoute(encodedPolyline) {
            // console.log("encodedPolyline: "+encodedPolyline)
            if (!map) {
              console.error("Map not initialized.");
              return;
            }

            const decodedPath = google.maps.geometry.encoding.decodePath(encodedPolyline);

            const routeLine = new google.maps.Polyline({
              path: decodedPath,
              geodesic: true,
              strokeColor: "#0000FF", // Blue
              strokeOpacity: 2.0,
              strokeWeight: 6,
            });

            routeLine.setMap(map);

            const bounds = new google.maps.LatLngBounds();
            decodedPath.forEach((latLng) => bounds.extend(latLng));
            map.fitBounds(bounds);

            // NOTE: pickup/stop/drop markers are no longer created here — see
            // drawStopMarkers() below, which places one pin per stop (including
            // pickup and the final drop) in actual visit order, not just the two
            // endpoints of the polyline.
        }

        /* ============================================================
         * NEW: clear any previously-drawn pickup/stop/drop markers.
         * Called at the start of drawStopMarkers() so re-running the route
         * (e.g. after adding/removing a stop) never leaves stale pins behind.
         * ============================================================ */
        function clearStopMarkers() {
            for (var i = 0; i < stopMarkers.length; i++) {
                stopMarkers[i].setMap(null);
            }
            stopMarkers = [];
        }

        /* ============================================================
         * NEW: place one labeled marker per point on the route, in actual
         * visit order — pickup first, then every added stop in the order
         * they'll be visited, then the final drop last.
         *
         * stops: [{ lat, lng, text }, ...] already in visit order.
         * Labels run A, B, C, ... so with stops added you get e.g.
         * A = pickup, B = stop 1, C = stop 2, D = drop — instead of the
         * old behaviour where only the two route endpoints ever got a pin.
         * ============================================================ */
        function drawStopMarkers(stops) {
            clearStopMarkers();

            if (!map || !stops || stops.length === 0) {
                return;
            }

            var labels = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

            stops.forEach(function (stop, idx) {
                if (isNaN(stop.lat) || isNaN(stop.lng)) {
                    return;
                }

                var label = labels[idx] || String(idx + 1);
                var isFirst = idx === 0;
                var isLast = idx === stops.length - 1;
                var title = isFirst
                    ? 'Pickup Location' + (stop.text ? ': ' + stop.text : '')
                    : (isLast
                        ? 'Drop-off Location' + (stop.text ? ': ' + stop.text : '')
                        : 'Stop ' + idx + (stop.text ? ': ' + stop.text : ''));

                var marker = new google.maps.Marker({
                    position: { lat: stop.lat, lng: stop.lng },
                    map: map,
                    label: label,
                    title: title
                });

                if (stop.text) {
                    var stopInfoWindow = new google.maps.InfoWindow({
                        content: '<strong>' + label + '</strong><br>' + stop.text
                    });
                    marker.addListener('click', function () {
                        stopInfoWindow.open(map, marker);
                    });
                }

                stopMarkers.push(marker);
            });
        }

        google.maps.event.addDomListener(window, 'load', initialize);


        $(document).on('change','#price_for_ride',function () {
            var priceForRide = this.value;
            if(priceForRide == 1 || priceForRide == ''){
                $('#price_ride_value_div').hide();
                $('#promo_code').show();
            }else{
                $('#promo_code').hide();
                $('#price_ride_value_div').show();
            }

            $("#service").val("").trigger("change");
            $("#payment_method_id").val("").trigger("change");
            $("#service").css("border", "1px solid red");
            $("#payment_method_id").css("border", "1px solid red");
        });

        $(document).on('change','#segment', function(){
            var segment = this.value;
            if(segment == 2){
                $('#delivery_details_div').show();
            }else{
                $('#delivery_details_div').hide();
            }
            let user_id = $("#user_id").val();
            if(user_id && segment){
                $('#pickup_location').prop('disabled', false);
                $('#drop_location').prop('disabled', false);

            }
        });

        $(document).ready(function () {

            count = 0;

            // NEW: decide the Add-stop button's visibility right away, using the
            // merchant config already rendered into #multi_destination / #max__multi_count
            // — no need to wait for a #service 'change' event that might never fire.
            updateAddStopButtonVisibility();

            $(".add-more").click(function () {
                // console.log("Add More Location")
                var max_multi_count = document.getElementById('max__multi_count').value;
                var limit = max_multi_count;
                //return false;
                if (count == limit) {
                    return false;
                }
                count++;
                // console.log(count);
                var html = $(".copy").html();

                if (count > 1) {
                    $(".after-add-more").after(html);
                    var last = $('.after-add-more').next();
                } else {
                    $(".after-add-more").before(html);
                    var last = $('.after-add-more').prev();
                }

                $('.after-add-more').removeClass('after-add-more');
                last.addClass('after-add-more');
                $('#drop_loc').attr('q', count);
                $('#drop_loc').attr('id', 'drop_loc' + '_' + count);
                $('#drop_loc_latitude').attr('name', 'multiple_destination_lat' + '_' + count);
                $('#drop_loc_longitude').attr('name', 'multiple_destination_lng' + '_' + count);
                $('#drop_loc_latitude').attr('id', 'drop_loc_latitude' + '_' + count);
                $('#drop_loc_longitude').attr('id', 'drop_loc_longitude' + '_' + count);
                $('#drop_loc_text').attr('name', 'multiple_destination_text' + '_' + count);
                $('#drop_loc_text').attr('id', 'drop_loc_text' + '_' + count);

                // NEW: give this stop the same live location search/suggestions as
                // Pickup/Drop, instead of the old (dead) Google Places Autocomplete call.
                initDropLocSelect2(count);

                // NEW: total drop locations = 1 (main Drop) + however many extra stops exist now.
                $('#total_drop_location').val(count + 1);

                if (count > 1) {
                    $('.after-add-more').prev().children().children().attr('disabled', true)
                }
            });

            $("body").on("click", ".remove", function () {
                if ($(this).parents(".input-group").children().attr('q') < count)  // IT WILL RUN IF SOME EARLIER add more ELEMENT DELETED
                {   // DELETE ALL OF THE add more ELEMENTS, AS EARLIER WAS DELETED, COUNT VALUE DISTURBED
                    // console.log('Yes Small FROM .remove');
                    while (count >= 1) {
                        if ($('#drop_loc_' + count).parents().hasClass('after-add-more')) {
                            // console.log("Yes this have class (after-add-more) ");
                            let last = $('.after-add-more').next();
                            last.addClass('after-add-more');
                        }
                        $('#drop_loc_' + count).parents(".input-group").remove();
                        //$(this).parents(".input-group").remove();
                        count--;
                        // console.log(count);
                    }
                    // NEW: total drop locations = 1 (main Drop) + however many extra stops remain.
                    $('#total_drop_location').val(count + 1);
                    if (($('#drop_latitude').val()) && ($('#pickup_latitude').val())) {
                        $("#loader1").show();
                        // calculateAndDisplayRoute();
                        getDistance();
                        $("#loader1").hide();
                    }
                } else {
                    if ($(this).parents().hasClass('after-add-more')) {
                        if (count > 1) {
                            var last = $('.after-add-more').prev();
                        } else {
                            var last = $('.after-add-more').next();
                        }
                        last.addClass('after-add-more');
                        if (count > 1) {
                            last.children().children().attr('disabled', false);
                        }
                    }
                    $(this).parents(".input-group").remove();
                    count--;
                    // NEW: total drop locations = 1 (main Drop) + however many extra stops remain.
                    $('#total_drop_location').val(count + 1);
                    if (($('#drop_latitude').val()) && ($('#pickup_latitude').val())) {
                        $("#loader1").show();
                        // calculateAndDisplayRoute();
                        getDistance();
                        $("#loader1").hide();
                    }
                }
            });

        });

        $('#check_user_details').click(function () {
            var user_phone = $('[name="user_phone"]').val().trim();
            var country_id = $('[name="country"]').val();
            var phone_code= $('option:selected','[name="country"]').attr('data-phone');
            // alert(phone_code);
            if (user_phone == "") {
                swal("Enter Phone Number !")
                return false;
            } else {
                $("#loader1").show();
                var token = $('[name="_token"]').val();
                let number = phone_code + user_phone;
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: "SearchUser",
                    data: {user_phone: number,country_id:country_id},
                    success: function (data) {
                        // console.log(data);
                        if(data.id != null){
                            // Register user
                            $('#manual_user_type').val(2).trigger('change');
                            // console.log('Register');
                            $('#user_id').val(data.id);
                            $("#full_details").attr("href", "users/" + data.id);
                            $("#distance_unit").val(data.distance_unit);
                            // REMOVED: this API previously returned data.multi_destination as a
                            // JSON boolean (true/false) rather than 1/0. Writing that straight
                            // into the hidden field turned its value into the literal string
                            // "true", which broke the "== 1" checks in serviceDetails() and hid
                            // the Add-stop button. multi_destination / max__multi_count are now
                            // driven solely by $merchant->BookingConfiguration (see hidden inputs
                            // above), so they're left alone here.
                            // $("#isocode").val(data.iso);
                            $("#full_details_div").show();
                            if (data.user_gender == 1){
                                $('#driver_gender_div').hide();
                            }
                            $('#pickup_location').prop('disabled', false);
                            $('#drop_location').prop('disabled', false);
                        }else{
                            // console.log('Not Register');
                            // New user
                            $('#manual_user_type').val(1).trigger('change');
                            $('#user_id').val('');
                            swal("This Phone Number Is Not Registered !");
                            $("#full_details").removeAttr("href");
                            $("#full_details_div").hide();
                            $('#pickup_location').prop('disabled', false);
                            $('#drop_location').prop('disabled', false);
                        }
                    }, error: function (err) {
                        $('#user_id').val('');
                        swal("This Phone Number Is Not Registered !");
                        $("#full_details").removeAttr("href");
                        $("#full_details_div").hide();
                        // New user
                        $('#manual_user_type').val(1).trigger('change');
                        $('#pickup_location').prop('disabled', false);
                        $('#drop_location').prop('disabled', false);
                    }
                });
                $("#loader1").hide();
            }
        });

        // $(document).ready(function () {
        //     var radius = 1000;
        //     getMapDrivers(radius);
        // });

        $(document).on("change","#outstation_type_val",function(){
            checkOutstationType();
        });

        $(document).on('change',"#ride_radius_driver,#ride_radius_manual_driver,#driver_marker",function() {
            var radius = $(this).val();
            getMapDrivers(radius);
        });

        $(document).on("change","#vehicle_type",function(){
            getService();
        });

        $(document).on("change","#service",function(){
            var service = $('#service').val();
            if(service != 4 && service != 2){
                checkArea();
            }
            checkService();
            serviceDetails();
        });

        $(document).on("change","#package",function(){
            checkestimate();
        });

        function changeCountryCanter(s) {
            var country = s[s.selectedIndex].text;
            var index = country.indexOf(' (');
            var countryText = country.substring(0, index);
            // console.log(countryText);
            if (countryText != "") {
                var geocoder;
                geocoder = new google.maps.Geocoder();
                geocoder.geocode({'address': countryText}, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        map.setZoom(6);
                        map.setCenter(results[0].geometry.location)
                    }
                });
            }
        }

        function resetPromoCode(){
            var old_eta = $('#old_eta').val();
            var iso = document.getElementById("isocode").value;
            var estimate = "Fare Estimate : "+iso+" "+ old_eta;
            if(old_eta != ''){
                $('#estimate_fare_ride').html(estimate);
            }
            $('#estimate_fare').val(old_eta);
            $('#promo_code').prop('selectedIndex', 0);
            $('#payment_method_id').prop('selectedIndex', 0);
            return false;
        }


        $(document).ready(function () {
            $('#pickup_location').select2({
                placeholder: 'Search pickup location',
                minimumInputLength: {{$bookingConfig->autocomplete_start}},
                ajax: {
                    delay: 250,
                    method: 'POST',
                    url: "{{ route('search-places') }}",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    data: function (params) {
                        return {
                            keyword: params.term,
                            language: 'en',
                            for: 'USER',
                            user_id: $('#user_id').val()
                        };
                    },
                    processResults: function (response) {
                        // Transform your custom response to Select2 format
                        let results = [];

                        if (response.status === 'SUCCESS') {
                            response.data.forEach(item => {
                                item.google_response.forEach(g => {
                                    results.push({
                                        id: g.main_text, // or g.place_id if needed
                                        text: g.description
                                    });
                                });
                            });
                        }

                        return {
                            results: results
                        };
                    }
                }
            });



            $('#drop_location').select2({
                placeholder: 'Search drop location',
                minimumInputLength: {{$bookingConfig->autocomplete_start}},
                ajax: {
                    delay: 250,
                    method: 'POST',
                    url: "{{ route('search-places') }}",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    data: function (params) {
                        return {
                            keyword: params.term,
                            language: 'en',
                            for: 'USER',
                            user_id: $('#user_id').val()
                        };
                    },
                    processResults: function (response) {
                        // Transform your custom response to Select2 format
                        let results = [];

                        if (response.status === 'SUCCESS') {
                            response.data.forEach(item => {
                                item.google_response.forEach(g => {
                                    results.push({
                                        id: g.main_text, // or g.place_id if needed
                                        text: g.description
                                    });
                                });
                            });
                        }

                        return {
                            results: results
                        };
                    }
                }
            });

        });


        $('#pickup_location').on('select2:select', function (e) {
            const selectedData = e.params.data;

            const place = selectedData.text; ;
            console.log(place)
            // Call another AJAX request using this selected value
            $.ajax({
                url: "{{ route('reverse-geocode-google-location') }}",  // Replace with your actual route
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                data: {
                    address: place  // or keyword, depending on what you set as `id`
                },
                success: function (response) {
                    console.log(response,'fhjdbfdjshsvd');
                    const lat = response.data.results[0].geometry.location.lat;
                    const lng = response.data.results[0].geometry.location.lng
                    // console.log(lat, lng);

                    document.getElementById("pickup_latitude").value = lat;
                    document.getElementById("pickup_longitude").value = lng;
                    var pickupLat = $('#pickup_latitude').val();
                    var pickupLng = $('#pickup_longitude').val();
                    var merchant_id = {{$config->merchant_id}};
                    var segment = $('#segment').val();

                    setAreaAfterPickup("{{ csrf_token() }}", pickupLat, pickupLng, merchant_id, segment, "PICKUP");

                    if ($('#drop_latitude').val()) {
                        $("#loader1").show();
                        // calculateAndDisplayRoute();
                        // if($('[name="vehicle_type"]').val() != ''){
                            getDistance();
                        // }
                        $("#loader1").hide();
                    }

                    // You can now populate form fields, map, etc. with this data
                },
                error: function (xhr) {
                    console.error("Error fetching place details:", xhr.responseText);
                }
            });
        });



        $('#drop_location').on('select2:select', function (e) {
            const selectedData = e.params.data;

            const place = selectedData.text;

            // NEW: store the full address text right away — this is what
            // buildWaypointsJson() uses for the main drop entry in the combined
            // drop_location array, since the <select>'s own .value only holds
            // the short main_text.
            $('#drop_location_text').val(place);

            $.ajax({
                url: "{{ route('reverse-geocode-google-location') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                data: {
                    address: place
                },
                success: function (response) {
                    console.log(response,'rtfbddjbffj');
                    const lat = response.data.results[0].geometry.location.lat;
                    const lng = response.data.results[0].geometry.location.lng;
                    document.getElementById("drop_latitude").value = lat;
                    document.getElementById("drop_longitude").value = lng;
                    checkArea();

                    var merchant_id = {{$config->merchant_id}};
                    var segment = $('#segment').val();
                    setAreaAfterPickup("{{ csrf_token() }}", lat, lng, merchant_id, segment, "DROP");

                },
                error: function (xhr) {
                    console.error("Error fetching place details:", xhr.responseText);
                }
            });
        });

        /* ============================================================
         * NEW: live location search for the extra "Add" stops.
         * Same Select2 + search-places pattern as Pickup/Drop above,
         * just parameterized by stop index so it can be reused for
         * every stop row that gets added.
         * ============================================================ */
        function initDropLocSelect2(stopIndex) {
            var selector = '#drop_loc_' + stopIndex;

            $(selector).select2({
                placeholder: 'Search stop ' + stopIndex + ' location',
                minimumInputLength: {{$bookingConfig->autocomplete_start}},
                ajax: {
                    delay: 250,
                    method: 'POST',
                    url: "{{ route('search-places') }}",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    data: function (params) {
                        return {
                            keyword: params.term,
                            language: 'en',
                            for: 'USER',
                            user_id: $('#user_id').val()
                        };
                    },
                    processResults: function (response) {
                        let results = [];

                        if (response.status === 'SUCCESS') {
                            response.data.forEach(item => {
                                item.google_response.forEach(g => {
                                    results.push({
                                        id: g.main_text,
                                        text: g.description
                                    });
                                });
                            });
                        }

                        return {
                            results: results
                        };
                    }
                }
            });

            $(selector).on('select2:select', function (e) {
                const selectedData = e.params.data;
                const place = selectedData.text;

                // Store the full address text right away — this is what
                // buildWaypointsJson() reads for drop_location, since the
                // underlying <select>'s own .value only holds the short main_text.
                $('#drop_loc_text_' + stopIndex).val(place);

                $.ajax({
                    url: "{{ route('reverse-geocode-google-location') }}",
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    data: {
                        address: place
                    },
                    success: function (response) {
                        const lat = response.data.results[0].geometry.location.lat;
                        const lng = response.data.results[0].geometry.location.lng;

                        $('#drop_loc_latitude_' + stopIndex).val(lat);
                        $('#drop_loc_longitude_' + stopIndex).val(lng);

                        resetPromoCode();

                        if ($('#drop_latitude').val() && $('#pickup_latitude').val()) {
                            $("#loader1").show();
                            getDistance();
                            $("#loader1").hide();
                        }
                    },
                    error: function (xhr) {
                        console.error("Error fetching place details:", xhr.responseText);
                    }
                });
            });
        }


        function setAreaAfterPickup(token, pickupLat, pickupLng, merchant_id, segment, type=""){
            let selected_pickup_area = $("#selected_pickup_area").val();
            let already_in_geofence = $("#is_geofence").val();
            let manual_area = $('#manual_area').val();
            let req_data = {latitude: pickupLat,longitude:pickupLng,merchant_id :merchant_id,segment_id: segment, already_in_geofence: already_in_geofence ,  selected_pickup_area: selected_pickup_area, manual_area: manual_area};

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "checkArea",
                data: req_data,
                success:function (data) {
                    if(data.result == 1){
                        $('#manual_area').val(data.area_id);
                        $('#vehicle_type').html(data.vehicle_types)
                        $("#is_geofence").val(data.is_geofence);
                        if(type === "PICKUP"){
                            $("#selected_pickup_area").val(data.area_id);
                        }

                        // if (place.geometry.viewport) {
                        //     map.fitBounds(place.geometry.viewport);
                        // } else {
                        //     map.setCenter(place.geometry.location);
                        //     map.setZoom(12);
                        // }
                    }else{

                        document.getElementById("pickup_latitude").value = "";
                        document.getElementById("pickup_longitude").value = "";
                        document.getElementById("pickup_location").value = "";
                        document.getElementById("drop_latitude").value = "";
                        document.getElementById("drop_longitude").value = "";
                        document.getElementById("drop_location").value = "";

                        $("#vehicle_type_id").val("").trigger("change");
                        $("#service").css("border", "1px solid red");
                        $("#service").val("").trigger("change");
                        $("#service").css("border", "1px solid red");
                        $("#payment_method_id").val("").trigger("change");
                        $("#payment_method_id").css("border", "1px solid red");

                        alert(data.message);

                        return false;
                    }
                }
            });
        }


        // function calculateAndDisplayRoute() {
        //     var service = $('#service').val();
        //     var waypts = [];

        //     const pickup_lat_lon = document.getElementById('pickup_latitude').value+","+document.getElementById('pickup_longitude').value;
        //     const drop_lat_lon = document.getElementById('drop_latitude').value+","+document.getElementById('drop_longitude').value;

        //     if(service == 2){ // rental case
        //         getDistanceData(pickup_lat_lon, drop_lat_lon, waypts, function (err, data) {
        //             if (!err) {
        //                 // console.log("err: "+err);
        //                 // console.log("data: "+JSON.stringify(data));
        //                 // console.log(pickup_lat_lon, drop_lat_lon, waypts);
        //                 drawRoute(data.data.poly_point);
        //             }
        //         });
        //     }
        //     else{  // normal or outstation
        //         for (var i = count; i >= 1; i--) {
        //             if ($('#drop_loc_' + i).val()) {
        //                 waypts.push({
        //                     location: $('#drop_loc_' + i).val(),
        //                     stopover: true
        //                 });
        //             }
        //         }
        //         waypts.reverse();
        //         getDistanceData(pickup_lat_lon, drop_lat_lon, waypts, function (err, data) {
        //             if (!err) {
        //                 // console.log("err: "+err);
        //                 // console.log("data: "+JSON.stringify(data));
        //                 // console.log(pickup_lat_lon, drop_lat_lon, waypts);
        //                 drawRoute(data.data.poly_point);
        //             }
        //         });
        //     }
        // }


        function getDistanceData(pickup_location, drop_location, waypts,  callback){
            console.log('hrgegfhdbbdjd');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': "{{csrf_token()}}"
                },
                method: 'POST',
                url: "google-direction-data",
                data: {from: pickup_location ,to: drop_location, waypts: waypts, merchant_id: {{$config->merchant_id}}},
                success:function (data) {
                    if(data.status == "SUCCESS"){
                         callback(null, data);
                    }else{
                        alert(data.message);
                        document.getElementById("pickup_latitude").value = "";
                        document.getElementById("pickup_longitude").value = "";
                        document.getElementById("pickup_location").value = "";
                         callback("ERROR");
                    }
                },
                error: function(err){
                    console.log(err);
                    callback("ERROR");
                }
            });
        }


        // function getDistanceMatrixData(coord_string, dest, callback){
        //     // console.log(coord_string, dest);
        //     $.ajax({
        //         headers: {
        //             'X-CSRF-TOKEN': "{{csrf_token()}}"
        //         },
        //         method: 'POST',
        //         url: "google-distance-matrix-data",
        //         data: {coord_string: coord_string ,dest: dest},
        //         success:function (data) {
        //             if(data.status == "SUCCESS"){
        //                  callback(null, data);
        //             }else{
        //                 alert(data.message);
        //                 document.getElementById("pickup_latitude").value = "";
        //                 document.getElementById("pickup_longitude").value = "";
        //                 document.getElementById("pickup_location").value = "";
        //                  callback("ERROR");
        //             }
        //         },
        //         error: function(err){
        //             console.log(err);
        //             callback("ERROR");
        //         }
        //     });
        // }

        function getDistance() {

            // NEW: don't calculate distance or draw the route/polyline until a service
            // has actually been selected. Selecting pickup / drop / an added stop should
            // only ever store its lat/lng — the route (and its markers) should only be
            // computed and drawn once #service has a value, which is exactly the moment
            // the existing onchange="getDistance()" on #service fires. This mirrors how
            // checkArea() already withholds getDistance() until pickup/drop area checks
            // pass, and stops the map/polyline from jumping around every time a stop is
            // added/edited before a service has even been chosen.
            if (!$('#service').val()) {
                return;
            }

            if ($('#service').val() == 2) {
                checkestimate();
            }
        
            const pickup_lat_lon = document.getElementById('pickup_latitude').value + "," + document.getElementById('pickup_longitude').value;
            const drop_lat_lon = document.getElementById('drop_latitude').value + "," + document.getElementById('drop_longitude').value;
        
            if (pickup_lat_lon.length <= 1 || drop_lat_lon.length <= 1) {
                return;
            }
        
            var waypts = [];
            // NEW: parallel ordered list (pickup -> each stop -> main drop) used to
            // place a map marker for every point on the route, not just the two ends.
            var mapStops = [];
            mapStops.push({
                lat: parseFloat(document.getElementById('pickup_latitude').value),
                lng: parseFloat(document.getElementById('pickup_longitude').value),
                text: $('#pickup_location option:selected').text() || ''
            });

            if (count > 0) {
                var tempMapStops = [];
                for (var i = count; i >= 1; i--) {
                    // NOTE: $('#drop_loc_' + i).val() is only the short place text
                    // (Select2's underlying <select> value), not coordinates — that
                    // was causing "Latitude must be between -90 and 90" errors from
                    // the direction API. Use the stop's actual lat/lng hidden fields instead.
                    var stopLat = $('#drop_loc_latitude_' + i).val();
                    var stopLng = $('#drop_loc_longitude_' + i).val();
                    if (stopLat && stopLng) {
                        waypts.push({
                            location: stopLat + ',' + stopLng,
                            stopover: true
                        });
                        tempMapStops.push({
                            lat: parseFloat(stopLat),
                            lng: parseFloat(stopLng),
                            text: $('#drop_loc_text_' + i).val() || ''
                        });
                    }
                }
                waypts.reverse();
                tempMapStops.reverse();
                mapStops = mapStops.concat(tempMapStops);
            }

            mapStops.push({
                lat: parseFloat(document.getElementById('drop_latitude').value),
                lng: parseFloat(document.getElementById('drop_longitude').value),
                text: $('#drop_location_text').val() || ''
            });
        
            getDistanceData(pickup_lat_lon, drop_lat_lon, waypts, function (err, data) {
        
                if (err) {
                    window.alert('Directions request failed due to ' + err.message);
                    return;
                }
        
                var total_distance = data.data.distance_in_meter;
                var total_time = data.data.time_in_min;
                document.getElementById("distance").value = total_distance;
                document.getElementById("ride_time").value = total_time;
                document.getElementById("estimate_distance").value = total_distance;
                document.getElementById("estimate_time").value = total_time + " mins";
        
                var dis_unit = document.getElementById("distance_unit").value;
                if (!dis_unit) {
                    dis_unit = 1;
                }
                var unit, Cal;
                if (dis_unit == 1) {
                    unit = "km";
                    Cal = 1000;
                } else if (dis_unit == 2) {
                    unit = "mi";
                    Cal = 1609;
                } else {
                    unit = "meter";
                    Cal = 1;
                }
                var displayDistance = (total_distance / Cal).toFixed(2);
                document.getElementById("distance_and_time").innerText = "Distance: " + displayDistance + " " + unit + " Time: " + total_time + " Mins";
        
                if (data.data.poly_point) {
                    drawRoute(data.data.poly_point);
                }

                // NEW: place a marker for the pickup, every stop, and the drop — in
                // visit order — so adding a stop actually shows up on the map.
                drawStopMarkers(mapStops);
        
                if ($('[name="vehicle_type"]').val() != '') {
                    checkestimate();
                } else {
                    $('#estimate_fare_ride').html('');
                }
            });
        }

function checkestimate() {
    console.log('Inside estimate');
    var area = $('[name="manual_area"]').val();
    var distance = $('[name="distance"]').val();
    var distance_unit = $('[name="distance_unit"]').val();
    var ride_time = $('[name="ride_time"]').val();
    var service = $('[name="service"]').val();
    var vehicle_type = $('[name="vehicle_type"]').val();
    var token = $('[name="_token"]').val();
    var package_id = $('[name="package"]').val();
    var segment_id = $('[name="segment_id"]').val();
    var outstation_type_val = $('[name="outstation_type_val"]').val();
    var additional_support = $("#additional_support").val();
    var user_id  = $('#user_id').val();
    $('#estimate_fare').val('');
    if(vehicle_type == ''){
        $('#estimate_fare_ride').html("Please Select Vehicle For Estimate Price");
        return false;
    }
    var additional_support = $("#service option:selected").attr('additional_support');

    if (additional_support == 1 && outstation_type_val == 2) {
        if(package_id === ""){
            return false;
        }
    }
    // console.log(distance);
    //     console.log(ride_time);
    //     console.log(service);
    //     console.log(vehicle_type);
    //     console.log(area);
    //     console.log(distance_unit);
    //     console.log(package_id);
    //     console.log(outstation_type_val);
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': token
        },
        method: 'GET',
        url: "estimatePrice",
        data: {
            distance: distance,
            ride_time: ride_time,
            service: service,
            vehicle_type: vehicle_type,
            area: area,
            distance_unit : distance_unit || 1,
            package_id : package_id,
            outstation_type: outstation_type_val,
            segment_id: segment_id,
        },
        success: function (data) {
            if(data.result == 1){
                var priceCardFare = data.amount;
                $('#price_card_id').val(data.price_card_id);
                // console.log('priceCardFare :'+priceCardFare);
                var outstationFareTyep = $('#outstation_type_val').val();
                // console.log('outstationFareTyep :'+outstationFareTyep);
                if(service == 4 && $('#outstation_type_val').val() == 1){
                    priceCardFare = priceCardFare *2;
                }

                var manualPrice = parseInt($('#price_for_ride_value').val());
                var fare = parseInt($('#price_for_ride_value').val());
                // var iso = document.getElementById("isocode").value;
                var iso = $("#isocode").val(data.iso);
                var estimate = "Fare Estimate : "+ data.iso +" "+ priceCardFare;
                $('#estimate_fare').val(priceCardFare);
                fare = priceCardFare;
                if($('#price_for_ride').val() == 2){
        {{--                    estimate = "Fare Estimate : "+iso+" "+ manualPrice;--}}
        estimate = "Fare Estimate : "+data.iso+" "+ manualPrice;
        fare = manualPrice;
        $('#estimate_fare').val(manualPrice);
    }else if($('#price_for_ride').val() == 3){
        if(priceCardFare > manualPrice){
            estimate = "Fare Estimate : "+iso+" "+ manualPrice;
            fare = manualPrice;
            $('#estimate_fare').val(manualPrice);
        }else{
            estimate = "Fare Estimate : "+iso+" "+ priceCardFare;
            fare = priceCardFare;
            $('#estimate_fare').val(priceCardFare);
        }
    }
    // console.log('fare estimate : '+estimate);
    $('#estimate_fare_ride').html(estimate);
    $('#payment_method_id').attr('disabled', false);
    console.log("done")

    var price_card_id = $('#price_card_id').val();
    // console.log('price_card_id : '+price_card_id);
    // console.log('area : '+area);
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': token
        },
        method: 'POST',
        url: "getPromoCode",
        data: {
            fare: fare,
            user_id: user_id,
            price_card_id: price_card_id,
            manual_area: area,
        },
        success: function (data) {
            $('#promo_code').html(data);
        }
    });
}else{
    swal(data.message);
}
}
});
}

        /* ============================================================
         * NEW: Build the JSON waypoints array for extra "Add" stops.
         * Format matches what the driver tracking map expects:
         * [{"stop":"0","drop_latitude":..,"drop_longitude":..,"drop_location":"..","status":"1"}, ...]
         *
         * Only the stops added via the "Add" button are included here —
         * the main pickup_location / drop_location fields keep working
         * exactly as before and are submitted separately, unchanged.
         * If the user never clicks "Add" (count stays 0), this produces
         * an empty string, so a single-destination booking behaves
         * exactly like it did previously.
         * ============================================================ */
        // function buildWaypointsJson() {
        //     var stops = [];
        //     var stopIndex = 0;

        //     for (var i = 1; i <= count; i++) {
        //         // NOTE: drop_loc_text_<i> holds the full address (set on select2:select);
        //         // the underlying <select drop_loc_<i>> only carries the short main_text.
        //         var textEl = document.getElementById('drop_loc_text_' + i);
        //         var latEl = document.getElementById('drop_loc_latitude_' + i);
        //         var lngEl = document.getElementById('drop_loc_longitude_' + i);

        //         if (textEl && latEl && lngEl && textEl.value && latEl.value && lngEl.value) {
        //             stops.push({
        //                 stop: String(stopIndex),
        //                 drop_latitude: parseFloat(latEl.value),
        //                 drop_longitude: parseFloat(lngEl.value),
        //                 drop_location: textEl.value,
        //                 status: "1" // new stop, not yet completed
        //             });
        //             stopIndex++;
        //         }
        //     }

        //     var waypointsField = document.getElementById('waypoints_json');
        //     if (waypointsField) {
        //         waypointsField.value = stops.length > 0 ? JSON.stringify(stops) : '';
        //     }

        //     // NEW: combined drop_location array = extra stops + the main Drop as the
        //     // final entry, e.g.
        //     // [{"stop":"0",...},{"stop":"1",...},{"stop":"2",...(main drop)...}]
        //     //
        //     // Exactly one of (the <select id="drop_location">, this hidden field) carries
        //     // name="drop_location" at submit time — decided explicitly here, so it never
        //     // depends on field order in the DOM. Single-destination bookings (no stops)
        //     // keep the <select> submitting its normal value, unchanged from before.
        //     var dropSelect = document.getElementById('drop_location');
        //     var dropLocationField = document.getElementById('drop_location_full_json');

        //     if (stops.length > 0) {
        //         var combinedDropLocations = stops.slice(); // copy — keep `waypoints` above untouched

        //         var mainDropTextEl = document.getElementById('drop_location_text');
        //         var mainDropText = mainDropTextEl ? mainDropTextEl.value : '';
        //         var mainDropLat = document.getElementById('drop_latitude').value;
        //         var mainDropLng = document.getElementById('drop_longitude').value;

        //         if (mainDropText && mainDropLat && mainDropLng) {
        //             combinedDropLocations.push({
        //                 stop: String(combinedDropLocations.length),
        //                 drop_latitude: parseFloat(mainDropLat),
        //                 drop_longitude: parseFloat(mainDropLng),
        //                 drop_location: mainDropText,
        //                 status: "1"
        //             });
        //         }

        //         if (dropSelect) dropSelect.removeAttribute('name');
        //         if (dropLocationField) {
        //             dropLocationField.setAttribute('name', 'drop_location');
        //             dropLocationField.value = JSON.stringify(combinedDropLocations);
        //         }
        //     } else {
        //         if (dropSelect) dropSelect.setAttribute('name', 'drop_location');
        //         if (dropLocationField) {
        //             dropLocationField.removeAttribute('name');
        //             dropLocationField.value = '';
        //         }
        //     }

        //     // NEW: final, authoritative total_drop_location — 1 (main Drop) + only the
        //     // stops that actually have a location/lat/lng filled in (stopIndex), so a
        //     // half-filled stop row left on screen doesn't inflate the count.
        //     var totalDropLocationField = document.getElementById('total_drop_location');
        //     if (totalDropLocationField) {
        //         totalDropLocationField.value = stopIndex + 1;
        //     }
        // }
        function buildWaypointsJson() {
        // Collect only the extra "Add" stops (not the main drop yet).
        var addedStops = [];
    
        for (var i = 1; i <= count; i++) {
            // NOTE: drop_loc_text_<i> holds the full address (set on select2:select);
            // the underlying <select drop_loc_<i>> only carries the short main_text.
            var textEl = document.getElementById('drop_loc_text_' + i);
            var latEl = document.getElementById('drop_loc_latitude_' + i);
            var lngEl = document.getElementById('drop_loc_longitude_' + i);
    
            if (textEl && latEl && lngEl && textEl.value && latEl.value && lngEl.value) {
                addedStops.push({
                    drop_latitude: parseFloat(latEl.value),
                    drop_longitude: parseFloat(lngEl.value),
                    drop_location: textEl.value,
                    status: "1" // new stop, not yet completed
                });
            }
        }
    
        var dropSelect = document.getElementById('drop_location');
        var dropLocationField = document.getElementById('drop_location_full_json');
        var waypointsField = document.getElementById('waypoints_json');
    
        var mainDropTextEl = document.getElementById('drop_location_text');
        var mainDropText = mainDropTextEl ? mainDropTextEl.value : '';
        var mainDropLatEl = document.getElementById('drop_latitude');
        var mainDropLngEl = document.getElementById('drop_longitude');
        var mainDropLat = mainDropLatEl ? mainDropLatEl.value : '';
        var mainDropLng = mainDropLngEl ? mainDropLngEl.value : '';
    
        if (addedStops.length > 0 && mainDropText && mainDropLat && mainDropLng) {
            // index 0 = the main Drop location.
            var combined = [{
                stop: "0",
                drop_latitude: parseFloat(mainDropLat),
                drop_longitude: parseFloat(mainDropLng),
                drop_location: mainDropText,
                status: "1"
            }];
    
            // index 1, 2, 3, ... = each "Add"-button stop, in the order added.
            addedStops.forEach(function (stop, idx) {
                stop.stop = String(idx + 1);
                combined.push(stop);
            });
    
            // Exactly one of (the <select id="drop_location">, this hidden field) carries
            // name="drop_location" at submit time — single-destination bookings (no stops)
            // keep the <select> submitting its normal value, unchanged from before.
            if (dropSelect) dropSelect.removeAttribute('name');
            if (dropLocationField) {
                dropLocationField.setAttribute('name', 'drop_location');
                dropLocationField.value = JSON.stringify(combined);
            }
    
            // "waypoints" now mirrors the same combined, reordered array.
            if (waypointsField) {
                waypointsField.value = JSON.stringify(combined);
            }
        } else {
            if (dropSelect) dropSelect.setAttribute('name', 'drop_location');
            if (dropLocationField) {
                dropLocationField.removeAttribute('name');
                dropLocationField.value = '';
            }
            if (waypointsField) {
                waypointsField.value = '';
            }
        }
    
        // total_drop_location = 1 (main Drop) + however many stops actually got filled in.
        var totalDropLocationField = document.getElementById('total_drop_location');
        if (totalDropLocationField) {
            totalDropLocationField.value = addedStops.length + 1;
        }
    }

        function RideType(val) {
    if (val == "1") {
        document.getElementById('start-div').style.display = 'none';
        document.getElementById('end-div').style.display = 'none';
        @if($bookingConfig->ride_later_on_admin == 1)
            document.getElementById('manual_driver_selection').style.display = 'block';
        @endif
        } else {
            document.getElementById('start-div').style.display = 'block';
            document.getElementById('end-div').style.display = 'block';
        @if($bookingConfig->ride_later_on_admin == 1)
            document.getElementById('manual_driver_selection').style.display = 'none';
        @endif
        }
    }

    function get_this_element(object_data) {
    $("#q_value").val($(object_data).attr('q'));
    }

    function check_element_on_blur(object_data) {
    if (!$('#drop_loc_' + $(object_data).attr('q')).val()) {
    $("#drop_loc_latitude_" + $(object_data).attr('q')).val(null);
    $("#drop_loc_longitude_" + $(object_data).attr('q')).val(null);
    //document.getElementById("distance_and_time").innerText = null;
    }
    }

    // function getMultiDropLoction(data){
    // if(data.value.length > {{$bookingConfig->autocomplete_start}}){
//                 var addmore = new google.maps.places.Autocomplete(
//                     (document.getElementById('drop_loc' + '_' + count)));

//                 addmore.addListener('place_changed', function () {
//                     var place = addmore.getPlace();
//                     $("#drop_loc_latitude_" + $("#q_value").val()).val(place.geometry.location.lat());
//                     $("#drop_loc_longitude_" + $("#q_value").val()).val(place.geometry.location.lng());

//                     if (($('#drop_latitude').val()) && ($('#pickup_latitude').val())) {
//                         calculateAndDisplayRoute();
//                         getDistance();
//                     }
//                 });
//             }
//         }

        function validation() {
            var country = document.getElementById('country').value;
            var user = document.getElementById('user_id').value;
            var manual_area = document.getElementById('manual_area').value;
            var service = document.getElementById('service').value;
            var vehicle_type = document.getElementById('vehicle_type').value;
            var package = document.getElementById('package').value;
            var pickup_location = document.getElementById('pickup_location').value;
            var drop_location = document.getElementById('drop_location').value;
            var payment_method_id = document.getElementById('payment_method_id').value;
            var driver_request = document.getElementsByName('driver_request')[0].value;

            document.getElementById("manualDispatchForm").addEventListener("submit", function(e) {
                const btn = document.getElementById("manualDispatch");
                btn.disabled = true;               // disable button
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...'; // feedback
            });

            let price_for_ride = $("#price_for_ride").val();
            if(price_for_ride == 2){
                let price_for_ride_value = $("#price_for_ride_value").val();
                if(!price_for_ride_value){
                    sweetalert("Enter Fix Amount Value !");
                    return false;
                }

            }

            let old_eta = $("#old_eta").val();
            if (!old_eta || isNaN(old_eta)) {
                $("#service").val("").trigger("change");
                $("#service").css("border", "1px solid red");
                sweetalert("Price For Ride has been changed pls select service  and payment method again !");

                $("#payment_method_id").val("").trigger("change");
                $("#payment_method_id").css("border", "1px solid red");

                return false;
            }

            if (country == "") {
                sweetalert("@lang("$string_file.select_country")");
                return false;
            }
            if (user == "") {
                sweetalert("@lang("$string_file.user_not_found")");
                return false;
            }
            if (manual_area == "") {
                sweetalert("@lang("$string_file.select_service_area")");
                return false;
            }
            if (service == "") {
                sweetalert("@lang("$string_file.select_service_type")");
                return false;
            }
            if (vehicle_type == "") {
                sweetalert("@lang("$string_file.select_vehicle_type")");
                return false;
            }
            if ([2, 3].includes(service) && package == "") {
                sweetalert("@lang("$string_file.select_package")");
                return false;
            }
            if (document.getElementById("radio1").checked != true && document.getElementById('radio2').checked != true) {
                sweetalert("@lang("$string_file.select_ride_type")");
                return false;
            }
            if (pickup_location == "") {
                sweetalert("@lang("$string_file.enter_pickup_location")");
                return false;
            }
            if (payment_method_id == "") {
                sweetalert("@lang("$string_file.select_payment_method")");
                return false;
            }
            if (driver_request == 1 && document.getElementById('ride_radius_driver').value == "") {
                sweetalert("@lang("$string_file.enter_request_radius")");
                return false;

            }
            if (driver_request == 3 && document.getElementById('driver_id').value == "") {
                sweetalert("@lang("$string_file.select_driver")");
                return false;
            }
            if (driver_request == 3 && document.getElementById('ride_radius_manual_driver').value == "") {
                sweetalert("@lang("$string_file.enter_request_radius")");
                return false;
            }

            // NEW: build the multi-stop JSON right before the form actually submits,
            // so it reflects whatever stops are currently on screen.
            buildWaypointsJson();

            return true;
        }

        function getService() {
            $('#estimate_fare_ride').html('');
            $('#outstation_type').hide();
            $('#package_id').hide();
            $('#package').val('');
            var area_id = $('#manual_area').val();
            var segment = $('#segment').val();
            var vehicle_type_id = $('#vehicle_type option:selected').val();
            if (area_id != "" && vehicle_type_id != '') {
                $("#loader1").show();
                var token = $('[name="_token"]').val();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: "{{route('merchant.area.services')}}",
                    data: {
                        area_id: area_id,
                        segment_id:segment,
                        vehicle_type_id:vehicle_type_id,
                        segment_group:1,
                    },
                    success: function (data) {
                        $('#service').html(data);
                        $('#payment_method_id').attr('disabled', true);
                    }
                });
                $("#loader1").hide();
            }
            else{
                alert('{!! trans("$string_file.select_segment") !!}');
                $("#vehicle_type option:selected").prop('selected', false);
            }
        }

        function checkService() {
            // console.log('Inside Check Service');
            checkOutstationType();
            $('#estimate_fare_ride').html('');
            var val = $("#service option:selected").val();
            $("#loader1").show();
            $("#extra_charge").hide();
            $("#additional_support").val('');
            $("#outstation_type").hide();
            $("#package_id").hide();
            $("#package").prop("required",false);
            $("#package").html("<option value=''>@lang("$string_file.select_package")</option>");
            if (val !== "") {
                var token = $('[name="_token"]').val();
                var service_type_id = $("#service option:selected").val();
                var additional_support = $("#service option:selected").attr('additional_support');
                $("#additional_support").val(additional_support);

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: "<?php echo route('merchant.price.card.service.config') ?>",
                    data: {
                        service_type_id: service_type_id,
                        additional_support: additional_support,
                        merchant_id: "{{$bookingConfig->merchant_id}}"
                    },
                    success: function (data) {
                        // console.log(data);
                        if (additional_support == 1 || additional_support == 2) {
                            $("#package").prop("required",true);
                            $("#package").html(data); // its div of package or special city
                            if (additional_support == "1") {
                                $("#package_id").show();
        {{--$("#newText").text("@lang('admin.package')");--}}
        }
        else if (additional_support == 2) {
            $("#newText").text("@lang('admin.message542')");
                                $("#outstation_type").show();
                            }
                        }else{
                            checkPriceCard();
                        }
                    }
                });
            }
            $("#loader1").hide();
        }

        /* ============================================================
         * NEW: Add-stop button visibility, extracted out of serviceDetails().
         *
         * Why: this used to only run inside serviceDetails(), which itself only
         * runs on #service's 'change' event. A 'change' event only fires when
         * the value actually changes — if an area has just one service option
         * (or getService()'s AJAX response ends up auto-selecting the option
         * that was already selected), the browser never fires 'change',
         * serviceDetails() never runs, and the button is stuck at its hardcoded
         * default (display:none). Same merchant config, different outcome,
         * purely by timing — that's the "sometimes shows, sometimes doesn't" bug.
         *
         * The fix: this check only ever needed #multi_destination / #max__multi_count,
         * both already known the instant the page loads — so it no longer needs to
         * wait on any event at all. It's called once on document ready below, and
         * still called from serviceDetails() too so it stays in sync if those
         * hidden fields ever get updated later in the flow.
         * ============================================================ */
        function updateAddStopButtonVisibility() {
            var multi_destination = $('#multi_destination').val();
            var max_multi_count = $('#max__multi_count').val();
            var multiDestinationAllowed = (multi_destination == 1 && max_multi_count > 0);

            if (multiDestinationAllowed) {
                $('#add_loc').css("display", "block");
            } else {
                $('#add_loc').css("display", "none");

                // Merchant doesn't allow multi-destination (or hit its stop limit) ->
                // clear out any stop fields that may already be on screen.
                for (var i = count; i >= 1; i--) {
                    if ($('#drop_loc_' + i).val()) {
                        $('#drop_loc_' + i).val('');
                        $("#drop_loc_latitude_" + i).val('');
                        $("#drop_loc_longitude_" + i).val('');
                        if ($('#drop_loc_' + i).parents().hasClass('after-add-more')) {
                            // console.log("Yes this have class (after-add-more) ");
                            let last = $('.after-add-more').next();
                            last.addClass('after-add-more');
                        }
                        $('#drop_loc_' + i).parents(".input-group").remove();
                        //$(this).parents(".input-group").remove();
                        count--;
                    }
                }
                // NEW: total drop locations = 1 (main Drop) + however many extra stops remain (0 here).
                $('#total_drop_location').val(count + 1);
            }
        }

        function serviceDetails() {
            // console.log('Inside Service details.');
            var service = $('#service').val();
            var drop_latitude = $('#drop_latitude').val();
            var drop_longitude = $('#drop_longitude').val();
            var manual_area = $('#manual_area').val();
            $('#outstation_type').hide();
            $('#package').val('');

            // NOTE: the "Add stop" button no longer depends on #service at all —
            // see updateAddStopButtonVisibility() above for why.
            updateAddStopButtonVisibility();

            // Outstation-specific UI/check — independent of the multi-destination setting above.
            if(service == 4){
                $('#outstation_type').show();
                if(drop_latitude != ''){
                    var token = $('[name="_token"]').val();
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        method: 'POST',
                        url: "checkOutstationDropArea",
                        data: {
                            area_id: manual_area,
                            service_type: service,
                            latitude: drop_latitude,
                            longitude: drop_longitude
                        },
                        success: function (data) {
                            // console.log(data);
                            if(data.result == 0){
                                alert(data.message);
                                $('#drop_location').val('');
                                $("#drop_latitude").val('');
                                $("#drop_longitude").val('');
                            }
                        }
                    });
                }
            }

            // Rental route recalculation — independent of the multi-destination setting above.
            // FIX: calculateAndDisplayRoute() doesn't exist (it's commented out further down
            // in this file), so selecting the rental service threw a ReferenceError here and
            // the map/route never updated. getDistance() covers the same job — fetching the
            // route and redrawing both the polyline and the pickup/stop/drop markers.
            if(service == 2){
                getDistance();
            }
        }

        function checkOutstationType() {
            // console.log('inside check outstation type');
            $("#package_id").hide();
            var additional_support = $("#service option:selected").attr('additional_support');
            var outstation_type_val = $("#outstation_type_val option:selected").val();
            // console.log(additional_support);
            // console.log(outstation_type_val);
            $('#package').prop('selectedIndex', 0);
            if(additional_support == 2 && outstation_type_val == 1)
            {
                checkestimate();
                $("#package").prop("required",false);
            }
            else if(additional_support == 2 && outstation_type_val == 2)
            {
                $("#package_id").show();
            }
        }

        function getMapDrivers(radius_val) {
            // if(this.id = 'driver_marker'){
            //     var pickup_latitude = '';
            //     var drop_latitude = '';
            //     var service = '';
            //     var vehicle_type = '';
            //     var radius = '';
            //     var pickup_longitude = '';
            //     var manual_area = '';
            //     var drop_longitude = '';
            //     var driver_gender = ''
            //     var distance_unit = '';
            // }else{
            var pickup_latitude = $('[name="pickup_latitude"]').val();
            var drop_latitude = $('[name="drop_latitude"]').val();
            var service = $('[name="service"]').val();
            var vehicle_type = $('[name="vehicle_type"]').val();
            var radius = radius_val;
            // console.log(radius);
            var pickup_longitude = $('[name="pickup_longitude"]').val();
            var manual_area = $('[name="manual_area"]').val();
            var drop_longitude = $('[name="drop_longitude"]').val();
            var driver_gender = $('[name="driver_gender"]').val();
            var distance_unit = document.getElementById("distance_unit").value;
            // }
            var token = $('[name="_token"]').val();
            var type = document.getElementById('driver_marker').value;

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "{{ route('getDriverOnMap') }}",
                data: {
                    radius: radius,
                    manual_area: manual_area,
                    pickup_latitude: pickup_latitude,
                    pickup_longitude: pickup_longitude,
                    drop_latitude: drop_latitude,
                    drop_longitude: drop_longitude,
                    service: service,
                    vehicle_type: vehicle_type,
                    driver_gender : driver_gender,
                    distance_unit: distance_unit,
                    type: type,
                },
                success: function (data) {
                    driverLocations = JSON.parse(data);
                    driverLocations = driverLocations['map_markers'];
                    infowindow = new google.maps.InfoWindow();
                    for (var f = 0; f < driverMarkers.length; f++) {
                        driverMarkers[f].setMap(null);
                    }
                    for (var i = 0; i < driverLocations.length; i++) {
                        newName = driverLocations[i]['marker_name'];
                        marker_number = driverLocations[i]['marker_number'];
                        icon = driverLocations[i]['marker_icon'];
                        marker_image = driverLocations[i]['marker_image'];
                        email = driverLocations[i]['marker_email'];
                        newLatitude = driverLocations[i]['marker_latitude'];
                        newLongitude = driverLocations[i]['marker_longitude'];
                        markerlatlng = new google.maps.LatLng(newLatitude, newLongitude);
                        content = '<table><tr><td rowspan="4"><img src="' + marker_image + '" height="50" width="50"></td></tr><tr><td>&nbsp;&nbsp;Email: </td><td><b>' + email + '</b></td></tr><tr><td>&nbsp;&nbsp;Mobile: </td><td><b>+' + marker_number + '</b></td></tr></table>';
                        var drivermarker = new google.maps.Marker({
                            map: map,
                            title: newName,
                            position: markerlatlng,
                            icon: icon
                        });
                        google.maps.event.addListener(drivermarker, 'click', (function (drivermarker, content, infowindow) {
                            return function () {
                                infowindow.setContent(content);
                                infowindow.open(map, drivermarker);
                                map.panTo(this.getPosition());
                                map.setZoom(15);
                            };
                        })(drivermarker, content, infowindow));
                        driverMarkers.push(drivermarker);
                    }
                }, error: function (e) {
                    // console.log(e);
                }
            });
        }

        function checkPriceCard() {
            var service = $('#service').val();
            var vehicle_type = $('#vehicle_type').val();
            var manual_area = $('#manual_area').val();
            var package_id = $('#package').val();
            var token = $('[name="_token"]').val();
            // console.log('vehicle_type :'+vehicle_type);
            // console.log('Service_Type :'+service);
            if (vehicle_type === "" || service === "" || manual_area === "") {
                alert("Some Data Missing");
                return false;
            }
            if((service == 2 || service == 4) && package_id === ""){
                return false;
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': token
                },
                method: 'POST',
                url: "checkPriceCard",
                data: {
                    vehicle_type: vehicle_type,
                    service: service,
                    area: manual_area,
                    package_id: package_id,
                },
                success: function (data) {
                    if (data.result == 0) {
                        alert(data.message);
                        return false;
                    }
                }
            });
            $("#loader1").hide();
        }

        function checkArea() {
            console.log('check area');
            $('#estimate_fare_ride').html('');
            let selected_pickup_area = $("#selected_pickup_area").val();
            var pickupLat = $('#drop_latitude').val();
            var pickupLng = $('#drop_longitude').val();
            var merchant_id = {{$config->merchant_id}};
            var token = $('[name="_token"]').val();
            var service = $('#service').val();
            var manual_area = $('#manual_area').val();
            var already_in_geofence = $("#is_geofence").val();
            console.log(service,'lll');
            var segment = $('#segment').val();
            if(service != 4 && service !=2 && service){
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: "checkArea",
                    data: {latitude: pickupLat,longitude:pickupLng,merchant_id :merchant_id,service:service,manual_area:manual_area,segment_id: segment, already_in_geofence: already_in_geofence, selected_pickup_area: selected_pickup_area},
                    success:function (data) {
                        console.log(data,"hello");
                        if(data.result == 1){
                            if ($('#pickup_latitude').val()) {
                                $("#loader1").show();
                                // FIX: this used to call calculateAndDisplayRoute() here, but that
                                // function is commented out further down in this file — calling it
                                // threw a ReferenceError immediately, which meant getDistance() on
                                // the very next line (the call that actually redraws the route AND
                                // the pickup/stop/drop markers) never ran. getDistance() already
                                // does everything calculateAndDisplayRoute() was meant to do.
                                getDistance();
                                $("#manual_area").val(data.area_id);
                                $("#loader1").hide();
                                return true
                            }
                        }else{
                            // alert(data.message);
                            // document.getElementById("drop_latitude").value = "";
                            // document.getElementById("drop_longitude").value = "";
                            // document.getElementById("drop_location").value = "";

                            $("#vehicle_type_id").val("").trigger("change");
                            $("#service").css("border", "1px solid red");
                            $("#service").val("").trigger("change");
                            $("#service").css("border", "1px solid red");
                            $("#payment_method_id").val("").trigger("change");
                            $("#payment_method_id").css("border", "1px solid red");

                            alert(data.message);
                            return false;
                        }
                    }
                });
            }
        }

        function checkZero(input) {
            if (input.value === "0") {
              alert("Value cannot be 0!");
              input.value = "";
            }

            $("#service").val("").trigger("change");
            $("#payment_method_id").val("").trigger("change");
            $("#service").css("border", "1px solid red");
            $("#payment_method_id").css("border", "1px solid red");
        }

    </script>
@endsection
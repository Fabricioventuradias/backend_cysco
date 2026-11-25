@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('merchant.fleet-company') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px"><i
                                            class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add_fleet_company")
                    </h3>
                </div>
                @php $id = isset($company->id) ? $company->id : NULL  @endphp
                <div class="panel-body container-fluid">
                    <section id="validation">
                        <form method="POST" class="steps-validation wizard-notification" id="fleetcompany-form"
                              name="taxicompany-form"
                              enctype="multipart/form-data" action="{{ route('merchant.fleet-company.save',$id) }}">
                            @csrf
                            <input type="hidden" name="id" value="{{$id}}">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="name"
                                               name="name" value="{{isset($company->FleetName) ? $company->FleetName : NULL}}"
                                               placeholder="" required>
                                        @if ($errors->has('name'))
                                            <label class="text-danger">{{ $errors->first('name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.fleet_owner") @lang("$string_file.name")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="fleet_owner_name"
                                               name="fleet_owner_name" value="{{isset($company->FleetOwnerName) ? $company->FleetOwnerName : NULL}}"
                                               placeholder="" required>
                                        @if ($errors->has('fleet_owner_name'))
                                            <label class="text-danger">{{ $errors->first('fleet_owner_name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.service_area")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" id="country_area_id" name="country_area_id">
                                            <option value=""> -- @lang("$string_file.select")--</option>
                                            @foreach($countryArea as $area)
                                                <option value="{{$area->id}}"
                                                        @if(!empty($company))@if($company->country_area_id == $area->id) selected @endif @endif>{{$area->CountryAreaName}}</option>
                                            @endforeach
                                        </select>
                                        @if ($errors->has('country_area_id'))
                                            <label class="text-danger">{{ $errors->first('country_area_id') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.fleet_owner") @lang("$string_file.phone")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="fleet_owner_phone"
                                               name="fleet_owner_phone" value="{{isset($company->fleet_owner_phone) ? $company->fleet_owner_phone : NULL}}"
                                               placeholder="" required>
                                        @if ($errors->has('fleet_owner_phone'))
                                            <label class="text-danger">{{ $errors->first('fleet_owner_phone') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lastName3">
                                            @lang("$string_file.fleet_owner") @lang("$string_file.email")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="fleet_owner_email" class="form-control" id="fleet_owner_email"
                                               name="fleet_owner_email" value="{{isset($company->fleet_owner_email) ? $company->fleet_owner_email : NULL}}"
                                               placeholder="" required>
                                        @if ($errors->has('fleet_owner_email'))
                                            <label class="text-danger">{{ $errors->first('fleet_owner_email') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.address")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="address"
                                               name="address"
                                               value="{{isset($company->Address) ? $company->Address : NULL}}"
                                               placeholder="" required>
                                        @if ($errors->has('address'))
                                            <label class="text-danger">{{ $errors->first('address') }}</label>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-4" id="password">
                                    <div class="form-group">
                                        <label for="emailAddress5">
                                            @lang("$string_file.password")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input class="form-control" type="password" name="password" id="password">
                                        @if ($errors->has('password'))
                                            <label class="text-danger">{{ $errors->first('password') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                <label>@lang("$string_file.payment_type") <span class="text-danger">*</span></label>
                                    <select name="payment_type" id="payment_type" class="form-control" required>
                                        <option value="">@lang("$string_file.select_payment_type")</option>
                                        <option value="1" {{isset($company->payment_type) && $company->payment_type == 1 ? 'selected' : ""}}>@lang("$string_file.commission_based")</option>
                                        {{--<option value="2" {{isset($company->payment_type) && $company->payment_type == 2 ? 'selected' : ""}}>@lang("$string_file.revenue_share")</option>
                                        <option value="3" {{isset($company->payment_type) && $company->payment_type == 3 ? 'selected' : ""}}>@lang("$string_file.fixed_rent")</option>--}}
                                    </select>
                                </div>
                                
                                {{-- Shown for Commission Based (1) and Revenue Share (2) --}}
                                <div class="col-md-4 payout-field" data-type="1 2" id="commission_percentage_wrap">
                                    <div class="form-group">
                                        <label for="commission_percentage">
                                            @lang("$string_file.commission_percentage")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input class="form-control" type="text" name="commission_percentage" id="commission_percentage" value="{{isset($company->commission_percentage) ? $company->commission_percentage : NULL}}">
                                        @if ($errors->has('commission_percentage'))
                                            <label class="text-danger">{{ $errors->first('commission_percentage') }}</label>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Shown only for Revenue Share (2) --}}
                                <div class="col-md-4 payout-field" data-type="2" id="driver_share_wrap">
                                    <div class="form-group">
                                        <label for="default_driver_share_percentage">
                                            @lang("$string_file.driver_share_percentage")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input class="form-control" type="text" name="default_driver_share_percentage" id="default_driver_share_percentage"
                                               value="{{isset($company->default_driver_share_percentage) ? $company->default_driver_share_percentage : NULL}}" placeholder="e.g. 70">
                                        @if ($errors->has('default_driver_share_percentage'))
                                            <label class="text-danger">{{ $errors->first('default_driver_share_percentage') }}</label>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Shown only for Fixed Rent (3) --}}
                                <div class="col-md-4 payout-field" data-type="3" id="rent_amount_wrap">
                                    <div class="form-group">
                                        <label for="rent_amount">
                                            @lang("$string_file.rent_amount")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input class="form-control" type="text" name="rent_amount" id="rent_amount"
                                               value="{{isset($company->rent_amount) ? $company->rent_amount : NULL}}">
                                        @if ($errors->has('rent_amount'))
                                            <label class="text-danger">{{ $errors->first('rent_amount') }}</label>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-4 payout-field" data-type="3" id="rent_frequency_wrap">
                                    <div class="form-group">
                                        <label for="rent_frequency">
                                            @lang("$string_file.rent_frequency")
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control" name="rent_frequency" id="rent_frequency">
                                            <option value="">-- @lang("$string_file.select")--</option>
                                            <option value="1" {{isset($company->rent_frequency) && $company->rent_frequency == 1 ? 'selected' : ""}}>@lang("$string_file.daily")</option>
                                            <option value="2" {{isset($company->rent_frequency) && $company->rent_frequency == 2 ? 'selected' : ""}}>@lang("$string_file.weekly")</option>
                                        </select>
                                        @if ($errors->has('rent_frequency'))
                                            <label class="text-danger">{{ $errors->first('rent_frequency') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions d-flex flex-row-reverse p-2">
                                    <button type="submit" class="btn btn-primary">
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
    function togglePayoutFields() {
        var type = $('#payment_type').val();
        $('.payout-field').hide().find('input, select').prop('required', false);
        $('.payout-field[data-type~="' + type + '"]').show().find('input, select').prop('required', true);
    }

    $(document).ready(function () {
        togglePayoutFields();
        $('#payment_type').on('change', togglePayoutFields);
    });
</script>
@endsection
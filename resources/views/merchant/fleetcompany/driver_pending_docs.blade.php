@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
           @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('fleetcompany.pending-driver') }}" data-toggle="tooltip">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply" title="@lang("$string_file.all_drivers")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-user" aria-hidden="true"></i>
                        @lang("$string_file.fleet_driver_profile")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <div id="user-profile">
                        <div class="col-md-12">
                            <h5>@lang("$string_file.personal_details")</h5>
                            <hr>
                            <div class="row">
                                <!-- Column -->
                                <div class="col-md-4 col-xs-12">
                                    <div class="card my-2 shadow  bg-white h-240">
                                        <div class="justify-content-center p-3">
                                            <div class="col-md-12 col-xs-12"
                                                 style="text-align:center;justify-content:center">
                                                <div class="mt-15 mb-15 h-100">
                                                    <img height="100" width="100" class="rounded-circle"
                                                         src="@if ($driver->profile_image) {{ get_image($driver->profile_image,'driver') }}@endif">
                                                </div>
                                            </div>
                                            <div class="overlay-box">
                                                <div class="user-content " style="text-align:center">
                                                    <h5 class="user-name mt-5 mb-3">@lang("$string_file.name")
                                                        : {{ is_demo_data($driver->fullName, $driver->Merchant) }}</h5>
                                                    <h6 class="user-job mb-3"> @lang("$string_file.email")
                                                        : {{ is_demo_data($driver->email, $driver->Merchant) }}</h6>
                                                    <h6 class="user-location mb-5"> @lang("$string_file.phone")
                                                        : {{ is_demo_data($driver->phoneNumber, $driver->Merchant) }}</h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                                <div class="col-md-8 col-xs-12 mt-20">
                                    <div class="row">
                                        <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                            <div class="border-left-success">
                                                <div class="">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="h6  text-uppercase mb-1">@lang("$string_file.service_area") </div>
                                                            <div class="h6 mb-0 font-weight-400"
                                                                 style="color:#7c8c9a">@if($driver->CountryArea->LanguageSingle) {{ $driver->CountryArea->LanguageSingle->AreaName }} @else  {{ $driver->CountryArea->LanguageAny->AreaName }} @endif</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- display driver wallet number--}}
                                        @if($config->driver_wallet_status == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3 ">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.wallet_money")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a"> {{ $driver->wallet_money }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($config->bank_details_enable == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6  text-uppercase mb-1">@lang("$string_file.account_holder_name")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->account_holder_name}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($config->driver_ssn_number_enable == 1 && isset($driver->driver_ssn_number) && !empty($driver->driver_ssn_number))
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6  text-uppercase mb-1">@lang("$string_file.driver_ssn_number")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->driver_ssn_number}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                    <div class="row">
                                        @if($config->driver_limit == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6  text-uppercase mb-1">@lang("$string_file.radius_limit")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if(isset($driver_config->radius)){{$driver_config->radius}}
                                                                    <a target="_blank"
                                                                       href="https://www.google.com/maps/place/{{ $driver_config->latitude }},{{  $driver_config->longitude }}"
                                                                       class="ml-2" title="View Map">
                                                                        <i class="fa fa-map" aria-hidden="true"></i>
                                                                    </a>
                                                                    @else -- @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty(Auth::user('merchant')->ApplicationConfiguration) && Auth::user('merchant')->ApplicationConfiguration->gender == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.gender")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if($driver->driver_gender == 1) @lang("$string_file.male") @else @lang("$string_file.female")  @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($config->bank_details_enable == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.bank_name")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->bank_name}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                    </div>
                                    <div class="row">
                                        @if($driver->account_type_id)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.account_type")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{ $driver->AccountType->name }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($driver->online_code)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.transaction_code")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{ $driver->online_code }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty(Auth::user('merchant')->ApplicationConfiguration) && Auth::user('merchant')->ApplicationConfiguration->smoker == 1)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1"> @lang("$string_file.smoke")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if($driver->DriverRideConfig) @if($driver->DriverRideConfig->smoker_type == 1)  @lang("$string_file.smoker") @else  @lang("$string_file.non_smoker") @endif @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="row">
                                        @if($config->bank_details_enable == 1)
                                            <div class="col-md-4 col-sm-4 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.account_number")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->account_number}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($config->driver_address)
                                            <div class="col-md-4 col-sm-6 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.address")</div>
                                                                @php $additionalData = json_decode($driver->driver_additional_data, true); @endphp
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">
                                                                    @if(!empty($additionalData))
                                                                        @foreach($additionalData as $key => $value)
                                                                            {{ trans("$string_file.$key") .' : '. is_demo_data(ucwords($value), $driver->Merchant) }}<br>
                                                                        @endforeach
                                                                    @else
                                                                        @lang("$string_file.data_not_found")
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty(Auth::user('merchant')->ApplicationConfiguration) && Auth::user('merchant')->ApplicationConfiguration->driver_vat_configuration == 1)
                                            <div class="col-md-4 col-sm-4 col-xs-12 py-2 mb-3">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6  text-uppercase mb-1">@lang("$string_file.vat_number")</div>
                                                                <div class="h6 mb-0 font-weight-400"
                                                                     style="color:#7c8c9a">{{$driver->vat_number}}({{($driver->is_vat_liable == 1) ? 'VAT liable' : 'VAT exempted'}})</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if(isset($driver->subscription_wise_commission) && $driver->subscription_wise_commission != NULL)
                                            <div class="col-md-4 col-sm-6 mb-3 col-xs-12 py-2">
                                                <div class="border-left-success">
                                                    <div class="">
                                                        <div class="row no-gutters align-items-center">
                                                            <div class="col mr-2">
                                                                <div class="h6 text-uppercase mb-1">@lang("$string_file.commission_type")</div>
                                                                @if($driver->subscription_wise_commission == 2)
                                                                    <div class="h6 mb-0 font-weight-400"
                                                                         style="color:#7c8c9a">
                                                                        @lang("$string_file.commission_based")
                                                                    </div>
                                                                @elseif($driver->subscription_wise_commission == 1)
                                                                    <div class="h6 mb-0 font-weight-400"
                                                                         style="color:#7c8c9a">
                                                                        @lang("$string_file.subscription_based")<br>
                                                                        @lang("$string_file.current_package")
                                                                        :- {{ isset($package_name) ? $package_name : '---' }}
                                                                    </div>
                                                                @else
                                                                    @lang("$string_file.data_not_found")
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-12 mt-20">
                                    <h5>@lang("$string_file.personal_document")</h5>
                                    <hr>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>@lang("$string_file.sn")</th>
                                                    <th>@lang("$string_file.document_name")</th>
                                                    <th>@lang("$string_file.document") </th>
                                                    <th>@lang("$string_file.expire_date")  </th>
                                                    <th>@lang("$string_file.status")</th>
                                                    <th>@lang("$string_file.uploaded_at") </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $doc_sn = 1 @endphp
                                                @foreach($driver->DriverDocument as $document)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$doc_sn}}
                                                        </td>
                                                        <td class="text-center">
                                                            {{ $document->Document->DocumentName }}
                                                        </td>
                                                        <td class="text-center">
                                                            @php
                                                               $is_pdf = check_file_extension($document->document_file);
                                                               $p_doc = get_image($document->document_file,'driver_document');
                                                            @endphp
                                                                @if($is_pdf)
                                                                     <embed src="{{ $p_doc }}" width="100px" height="100px" />
                                                                     <br>
                                                                     <a target="_blank" href="{{ $p_doc }}">@lang("$string_file.view")</a>
                                                                @else
                                                                     <a target="_blank" href="{{ $p_doc }}">
                                                                      <img src="{{ $p_doc }}" alt="avatar" style="width: 100px;height: 100px;">
                                                                     </a>
                                                                @endif

                                                        </td>
                                                        <!-- <td class="text-center">{!! convertTimeToUSERzone($document->expire_date, $driver->CountryArea->timezone,null,$driver->Merchant, 2) !!}</td> -->
                                                        <td class="text-center">{!! $document->expire_date  !!}</td>
                                                        <td class="text-center">
                                                            @switch($document->document_verification_status)
                                                                @case(1)
                                                                @lang("$string_file.pending_for_verification")
                                                                @break
                                                                @case(2)
                                                                @lang("$string_file.verified")
                                                                @break
                                                                @case(3)
                                                                @lang("$string_file.rejected")
                                                                @break
                                                                @case(4)
                                                                @lang("$string_file.expired")
                                                                @break
                                                            @endswitch
                                                        </td>
                                                        <td class="text-center">
                                                            {!! convertTimeToUSERzone($document->updated_at, $driver->CountryArea->timezone,null,$driver->Merchant) !!}
                                                        </td>
                                                    </tr>
                                                    @php $doc_sn = $doc_sn+1; @endphp
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @if(count($driver->DriverDocument) > 0 && (($driver->signupStep == 4 && $fleetDriver->fleet_signup_step < 3) || ($fleetDriver->is_approved != 1)))
                                    <div class="float-right mt-10">
                                            <a href="#" onclick="verifyDriver({{ $fleetDriver->id }}, 1)">
                                                <button class="btn btn-success float-right">@lang("$string_file.approve")</button>
                                            </a>
                                            @if($fleetDriver->is_approved != 2)
                                                <button class="btn btn-danger float-right mr-2"
                                                        data-toggle="modal"
                                                        data-target="#exampleModalCenter">@lang("$string_file.reject")
                                                </button>
                                            @endif
                                    </div>
                                @endif
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form class="form-group" action="{{ route('merchant.fleet-driver.reject') }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalCenterTitle">@lang("$string_file.reject_driver")</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" value="{{ $driver->id }}" name="driver_id">
                            <input type="hidden" value="{{ $fleetDriver->id }}" name="fleet_driver_id">
                            @if(count($driver->DriverDocument->where('document_verification_status', '=', 1)) > 0)
                                <div class="col-md-12">
                                    <h5>@lang("$string_file.personal_document")</h5>
                                </div>
                                @foreach($driver->DriverDocument as $document)
                                    @if($document->document_verification_status == 1)
                                        <div class="col-md-6">
                                            <input type="checkbox" value="{{ $document->id }}"
                                                   name="document_id[]"> {{ $document->Document->documentname }}
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                            <hr>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <textarea class="form-control" placeholder="@lang("$string_file.comments")" name="comment" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">@lang("$string_file.close")</button>
                        <button type="submit" class="btn btn-primary">@lang("$string_file.reject") </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function verifyDriver(fdriverId, status) {
            const url = `{{ route('merchant.fleet-driver.verify', [':fdriver_id', ':status']) }}`;
            const finalUrl = url.replace(':fdriver_id', fdriverId).replace(':status', status);
            
            swal({
                title: '@lang("$string_file.are_you_sure")',
                text: '@lang("$string_file.approve") @lang("$string_file.driver") ?', // customize this line as needed
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    window.location.href = finalUrl;
                }
            });
            
        }
    </script>
@endsection
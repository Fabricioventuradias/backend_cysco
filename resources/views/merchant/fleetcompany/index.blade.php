@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{route('merchant.fleet-company.add')}}">
                            <button type="button" title="@lang("$string_file.add_fleet_company")"
                                            class="btn btn-icon btn-success float-right"  style="margin:10px"><i class="wb-plus"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-building" aria-hidden="true"></i>
                        @lang("$string_file.fleet_company")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                            <thead>
                            <tr>
                                <th>@lang("$string_file.sn")</th>
                                <th>@lang("$string_file.fleet_company")</th>
                                <th>@lang("$string_file.service_area")</th>
                                <th>@lang("$string_file.email")</th>
                                <th>@lang("$string_file.phone")</th>
                                <th>@lang("$string_file.address")</th>
                                <th>@lang("$string_file.payment_type")</th>
                                <th>@lang("$string_file.wallet_money")</th>
                                <th>@lang("$string_file.fleet_earning")</th>
                                <th>@lang("$string_file.status")</th>
                                <th>@lang("$string_file.action")</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $sr = 1 @endphp
                            @foreach($fleets as $company)
                                <tr>
                                    <td>{{ $sr }}</td>
                                    <td>{{$company->FleetName}}</td>
                                    <td>{{$company->CountryArea->CountryAreaName}}</td>
                                    <td>{{$company->fleet_owner_email}}</td>
                                    <td>{{$company->fleet_owner_phone}}</td>
                                    <td>{{$company->Address}}</td>
                                    <td>
                                        @if($company->payment_type == 1) @lang("$string_file.commission_based")
                                        @elseif($company->payment_type == 2) @lang("$string_file.revenue_share")
                                        @elseif($company->payment_type == 3) @lang("$string_file.fixed_rent")
                                        @endif
                                    </td>
                                    <td>{{$company->wallet_money}}</td>
                                    <td>{{ number_format($company->booking_transactions_sum_fleet_earning ?? 0, 2) }}</td>
                                    <td>
                                        @if($company->fleet_status == 1)
                                            <span class="badge badge-success">@lang("$string_file.active")</span>
                                        @else
                                            <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{route('merchant.fleet-company.add',$company->id)}}"
                                                   data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                                   class="btn btn-sm btn-warning">
                                                    <i class="wb-edit"></i>
                                        </a>
                                        
                                        @if($company->fleet_status == 1)
                                            <a href="{{ route('fleetcompany.status',['id'=>$company->id,'status'=>2]) }}"
                                                   data-original-title="@lang("$string_file.inactive")" data-toggle="tooltip"
                                                   data-placement="top"
                                                   class="btn btn-sm btn-danger menu-icon btn_eye_dis action_btn"> <i
                                                            class="fa fa-eye-slash"></i> </a>
                                        @else
                                            <a href="{{ route('fleetcompany.status',['id'=>$company->id,'status'=>1]) }}"
                                                   data-original-title="@lang("$string_file.active")" data-toggle="tooltip"
                                                   data-placement="top"
                                                   class="btn btn-sm btn-success menu-icon btn_eye action_btn"> <i
                                                            class="icon fa-eye"></i> </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @include('merchant.shared.table-footer', ['table_data' => $fleets, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection
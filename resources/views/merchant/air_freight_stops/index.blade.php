@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <div class="panel-heading">
                <div class="panel-actions">
                    <a href="{{ route('air_freight.add_air_stops') }}">
                        <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-plus" title="@lang(" $string_file.add_air_stops")"></i>
                        </button>
                    </a>
                </div>
                <h3 class="panel-title"><i class="fa fa-map-marker" aria-hidden="true"></i>
                    @lang("$string_file.air_stop_management")
                </h3>
            </div>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn") </th>
                            <th>@lang("$string_file.air_freight_service_type")</th>
                            <th>@lang("$string_file.name") </th>
                            <th>@lang("$string_file.base_fare_amount") </th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $air_stops->firstItem() @endphp
                        @foreach($air_stops as $air_stop)
                        <tr>
                            <td>{{ $sr }}</td>
                            <td>
                                @if($air_stop->air_service_type_id == 1)
                                    @lang("$string_file.charter") (@lang("$string_file.cargo"))
                                @elseif ($air_stop->air_service_type_id == 2)
                                    @lang("$string_file.charter") (@lang("$string_file.passengers"))
                                @elseif ($air_stop->air_service_type_id == 3)
                                    @lang("$string_file.consolidation")(@lang("$string_file.cargo_consolidation_local"))
                                @else
                                    @lang("$string_file.consolidation")(@lang("$string_file.cargo_consolidation_international"))
                                @endif
                            </td>
                            <td><span class="long_text">
                                    @if(empty($air_stop->LanguageSingle))
                                        <span style="color:red">{{ trans("$string_file.not_added_in_english") }}</span>
                                        <span class="text-primary">( In {{ $air_stop->LanguageAny->LanguageName->name }}
                                            : {{ $air_stop->LanguageAny->name }}
                                            )</span>
                                    @else
                                    {{ $air_stop->LanguageSingle->name }}
                                    @endif

                                </span>
                            </td>
                            <td>
                                {{$air_stop->base_fare_amount}}
                            </td>
                            <td>
                                @if($air_stop->status == 1)
                                <span class="badge badge-success">@lang("$string_file.active")</span>
                                @else
                                <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                @endif
                            </td>
                            <td style="width: 100px;float: left">
                                <a href="{{ route('air_freight.edit_air_stops',$air_stop->id) }}">
                                    <button type="button" class="btn btn-sm btn-warning menu-icon btn_edit action_btn">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                </a>
                            </td>
                        </tr>
                        @php $sr++ @endphp
                        @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $air_stops, 'data' => []])
            </div>
        </div>
    </div>
</div>
@endsection

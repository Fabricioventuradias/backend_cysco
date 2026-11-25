@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading"><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                    <div class="panel-actions">
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-list-alt" aria-hidden="true"></i>
                        @lang("$string_file.air_freight_service_type")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table class="display nowrap table table-hover table-striped w-full" id="customDataTable" style="width:100%"
                           cellspacing="0">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.service_board")</th>
                            <th>@lang("$string_file.air_freight_service_type")</th>
                            <th>@lang("$string_file.air_service_type_image")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                            @php $sr = $airfreightservices->firstItem() @endphp
                            @foreach($airfreightservices as $service)
                                <tr>
                                    <td>{{ $sr }}</td>
                                    <td>
                                        {{$service->air_freight_type_name}}
                                    </td>
                                    <td>
                                        {{$service->air_service_type_name}}
                                    </td>
                                    <td>
                                        <img src="{{ get_image($service->service_type_image, 'air_service_type_image',$merchant_id)  }}" style="width:20%; height:20%; ">
                                    </td>
                                    <td>
                                        <a href="{{ route('merchant.air-freight-service-type.add',$service->id) }}"
                                               data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                               data-placement="top"
                                               class="btn menu-icon btn-warning action_btn"> <i
                                                        class="fa fa-edit"></i> </a>
                                    </td>
                                </tr>
                                @php $sr++  @endphp
                            @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $airfreightservices,'data'=>[]])
                </div>
            </div>
        </div>
    </div>
@endsection
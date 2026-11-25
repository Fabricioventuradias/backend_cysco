@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('merchant.weightparameter.add') }}">
                            <button type="button" title="@lang("$string_file.add_unit")" data-toggle="modal"
                                    data-target="#myModal"
                                    class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-plus"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-balance-scale" aria-hidden="true"></i>
                        {{--                        @lang('admin.weightunits_list')--}}
                        @lang("$string_file.air_weight_parameter")
                    </h3>
                </header>
                <div class="panel-body">
                    <table class="display nowrap table table-hover table-striped w-full" id="customDataTable"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.description")</th>
                            <th>@lang("$string_file.sequence")</th>
                            <th>@lang("$string_file.service")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = 1 @endphp
                        @foreach($weightParams as $weightparam)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    {{ isset($weightparam->LanguageSingle)?$weightparam->LanguageSingle->name:'' }}
                                </td>
                                <td>
                                    {{ isset($weightparam->LanguageSingle)?$weightparam->LanguageSingle->description:'' }}
                                </td>
                                <td>{{ isset($weightparam->sequence) ? $weightparam->sequence : "--" }}</td>
                                <td>
                                    @foreach($weightparam->ServiceTypes as $serviceType)
                                        {{$serviceType->ServiceName($weightparam->merchant_id)}}
                                    @endforeach
                                </td>
                                <td>
                                    @if($weightparam->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>
                                        <a href="{{ route('merchant.weightparameter.add',$weightparam['id']) }}"
                                           data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-warning menu-icon btn_edit action_btn"> <i
                                                    class="fa fa-edit"></i> </a>

                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection


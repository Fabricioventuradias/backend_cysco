@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                            <a href="{{ route('merchant.currency_conversion.create') }}">
                                <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                    <i class="wb-plus"
                                       title="@lang("$string_file.add_country")"></i>
                                </button>
                            </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.currency_exchange_conversion")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table class="display nowrap table table-hover table-stripedw-full" id="customDataTable"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.country")</th>
                            <th>@lang("$string_file.base_currency")</th>
                            <th>@lang("$string_file.local_currency")</th>
                            <th>@lang("$string_file.exchange_rate")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.updated_at")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                    <tbody>
                        @php $sr = $configs->firstItem(); @endphp
                        @foreach($configs as $config)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $config->Country->country_name ?? '--' }}</td>
                                <td>{{ $config->base_currency_code }}</td>
                                <td>{{ $config->local_currency_code }}</td>
                                <td>{{ $config->exchange_rate }}</td>
                                <td>
                                    @if($config->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($config->updated_at)->format('Y-m-d H:i') }}</td>
                                <td style="width:100px; float:left">
                                    <a href="{{ route('merchant.currency_conversion.edit', $config->id) }}"
                                       data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                       data-placement="top"
                                       class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                        <i class="wb-edit"></i>
                                    </a>
                                    <form action="{{ route('merchant.currency_conversion.destroy', $config->id) }}"
                                          method="POST" style="display:inline-block"
                                          onsubmit="return confirm('@lang("$string_file.are_you_sure")');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                data-original-title="@lang("$string_file.delete")" data-toggle="tooltip"
                                                data-placement="top"
                                                class="btn btn-sm btn-danger menu-icon action_btn">
                                            <i class="wb-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $configs, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection
@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route('merchant.category_air_service.add') }}">
                            <button type="button" title="@lang("$string_file.add_unit")" data-toggle="modal"
                                    data-target="#myModal"
                                    class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-plus"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="fa fa-th-large" aria-hidden="true"></i>
                        @lang("$string_file.category_air_service")
                    </h3>

                </header>
                <div class="panel-body">
                    <div class="alert alert-warning" role="alert">
                        <strong>Note:</strong> Please atleast make One Category for Air Freight in mandatory/basic setup.
                    </div>
                    <table class="display nowrap table table-hover table-striped w-full" id="customDataTable"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.category")</th>
                            <th>Air Service Type</th>
                            <th>Weight Parameter</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = 1 @endphp
                        @foreach($categories as $category)
                            @if(!in_array($category->id, $category_air_services))
                                @continue
                            @endif
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $category->Name($category->merchant_id) }}</td>
                                <td>
                                    @foreach(($categoryData[$category->id]['service_lines'] ?? []) as $line)
                                        <div>{{ $line }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach(($categoryData[$category->id]['weight_lines'] ?? []) as $line)
                                        <div>{{ $line }}</div>
                                    @endforeach
                                </td>
                                <td>
                                    <a href="{!! route('merchant.category_air_service.add',$category->id) !!}" class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                        <i class="wb-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
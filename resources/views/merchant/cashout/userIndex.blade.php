@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        {{--@if(Auth::user('merchant')->can('create_rider'))
                        <a href="{{route('driver.excel.cashout')}}" data-toggle="tooltip">
                                <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px">
                                    <i class="wb-download" title="@lang("$string_file.export") @lang("$string_file.excel")"></i>
                                </button>
                            </a>
                        @endif--}}
                    </div>
                    <h3 class="panel-title">
                        <i class="icon fa-taxi" aria-hidden="true"></i>
                        @lang("$string_file.user_cashout_management")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.user_id")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.cashout_amount")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action_by")</th>
                            <th>@lang("$string_file.transaction_id")</th>
                            <th>@lang("$string_file.comment")</th>
                            <th>@lang("$string_file.requested_at")</th>
                            <th>@lang("$string_file.action_date")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $user_cashout_requests->firstItem() @endphp
                        @foreach($user_cashout_requests as $user_cashout_request)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td><a href="">{{$user_cashout_request->User->user_merchant_id}}</a></td>
                                <td>
                                    <span class="long_text">
                                        {{$user_cashout_request->User->first_name .' '. $user_cashout_request->User->last_name}}<br>
                                        {{$user_cashout_request->User->UserPhone ?? ''}}<br>
                                        {{$user_cashout_request->User->email ?? ''}}
                                    </span>
                                </td>
                                <td>{{ $user_cashout_request->User->Country->isoCode . ' ' .$user_cashout_request->amount  }}</td>
                                <td>
                                    @switch($user_cashout_request->cashout_status)
                                        @case(0)
                                        <small class="badge badge-round badge-warning float-left">@lang("$string_file.pending")</small>
                                        @break;
                                        @case(1)
                                        <small class="badge badge-round badge-info float-left">@lang("$string_file.success")</small>
                                        @break;
                                        @case(2)
                                        <small class="badge badge-round badge-danger float-left">@lang("$string_file.rejected")</small>
                                        @break;
                                        @default
                                        ----
                                    @endswitch
                                </td>
                                <td>{{ ($user_cashout_request->action_by != '') ? $user_cashout_request->action_by : '---' }}</td>
                                <td>{{ ($user_cashout_request->transaction_id) ? $user_cashout_request->transaction_id : '---' }}</td>
                                <td>{{ ($user_cashout_request->comment != '') ? $user_cashout_request->comment : '---' }}</td>
                                <td>
                                    {!! $user_cashout_request->created_at!!}
                                </td>
                                <td>
                                   @if($user_cashout_request->cashout_status != 0)
                                    {!! $user_cashout_request->updated_at !!}
                                    @else
                                       ---
                                    @endif
                                </td>
                                    <td>
                                        <a href="{{ route('merchant.user.cashout_status',$user_cashout_request->id) }}"
                                           data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-primary menu-icon btn_edit action_btn">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $user_cashout_requests, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
@endsection

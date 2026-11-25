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
                            <a href="{{ route('charge-reasons.create') }}">
                                <button type="button"
                                        title="@lang("$string_file.add") @lang("$string_file.charge_reason")"
                                        class="btn btn-icon btn-success float-right" style="margin-top:10px">
                                    <i class="wb-plus"></i>
                                </button>
                            </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.parking_toll_charge_reason")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.charge_reason")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $charge_reasons->firstItem() @endphp
                        @foreach($charge_reasons as $charge_reason)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{ $charge_reason->Reason }}</td>
                                <td>
                                    @if($charge_reason->status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>
                                        <a href="{{ route('charge-reasons.edit',$charge_reason->id) }}"
                                           data-original-title="@lang("$string_file.edit")" data-toggle="tooltip"
                                           data-placement="top"
                                           class="btn btn-sm btn-primary menu-icon btn_edit action_btn"> <i
                                                    class="fa fa-edit"></i> </a>

                                            @if($charge_reason->status == 1)
                                                <a href="{{ route('charge-reasons.changestatus',['id'=>$charge_reason->id,'status'=>0]) }}"
                                                   data-original-title="@lang("$string_file.inactive")" data-toggle="tooltip"
                                                   data-placement="top"
                                                   class="btn btn-sm btn-danger menu-icon btn_eye_dis action_btn"> <i
                                                            class="fa fa-eye-slash"></i> </a>
                                            @else
                                                <a href="{{ route('charge-reasons.changestatus',['id'=>$charge_reason->id,'status'=>1]) }}"
                                                   data-original-title="@lang("$string_file.active")" data-toggle="tooltip"
                                                   data-placement="top"
                                                   class="btn btn-sm btn-success menu-icon btn_eye action_btn"> <i
                                                            class="fa fa-eye"></i> </a>
                                            @endif
                                        <button onclick="DeleteEvent({{ $charge_reason->id }})" type="submit"
                                                data-original-title="@lang("$string_file.delete")"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                class="btn btn-sm btn-danger btn_delete action_btn"><i
                                                        class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $charge_reasons, 'data' => []])
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script>
        function DeleteEvent(id) {
            var token = $('[name="_token"]').val();
            swal({
                title: "@lang("$string_file.are_you_sure")",
                text: "@lang("$string_file.delete_warning")",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((isConfirm) => {
                if (isConfirm) {
                    $.ajax({
                        headers: { 'X-CSRF-TOKEN': token },
                        type: "DELETE",
                        url: 'charge-reasons/' + id,
                    }).done(function (data) {
                        swal({ title: "DELETED!", text: data, type: "success" });
                        window.location.href = "{{ route('charge-reasons.index') }}";
                    });
                } else {
                    swal("@lang("$string_file.data_is_safe")");
                }
            });
        }
    </script>
@endsection
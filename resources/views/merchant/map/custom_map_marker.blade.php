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
                        <a href="{{route("custom.mapmarker.add")}}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                            <i class="wb-plus"
                                       title="@lang("$string_file.add_map_marker")"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.custom_map_marker")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table class="display nowrap table table-hover table-stripedw-full" id="customDataTable"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.name")</th>
                            <th>@lang("$string_file.map_marker_image")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $map_markers->firstItem() @endphp
                        @foreach($map_markers as $mapMarker)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>{{$mapMarker->name}}</td>
                                <td>
                                    <img src="{{ get_image($mapMarker->marker_image,'map_marker_image') }}" width="50px" height="50px">
                                </td>
                                <td>
                                    @if($mapMarker->status  == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('custom.mapmarker.delete', $mapMarker->id) }}" 
                                          method="POST" 
                                          class="delete-form" 
                                          style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-danger btn-sm btn-delete-marker" title="@lang("$string_file.delete")">
                                            <i class="fa fa-trash"></i> @lang("$string_file.delete")
                                        </button>
                                    </form>
                                </td>
                                
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $map_markers, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
$(document).on('click', '.btn-delete-marker', function(e) {
    e.preventDefault();
    var form = $(this).closest('form');

    swal({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        buttons: {
            cancel: {
                text: "Cancel",
                visible: true,
                className: "btn btn-secondary"
            },
            confirm: {
                text: "Yes, delete it!",
                className: "btn btn-danger"
            }
        },
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            form.submit();
        }
    });
});
</script>
@endsection
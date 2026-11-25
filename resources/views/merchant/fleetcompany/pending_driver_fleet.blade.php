@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-clock-o" aria-hidden="true"></i>
                        @lang("$string_file.pending_fleet_drivers")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable"
                           class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang("$string_file.fleet_company")</th>
                                <th>@lang("$string_file.driver")</th>
                                <th>@lang("$string_file.email")</th>
                                <th>@lang("$string_file.phone")</th>
                                <th>@lang("$string_file.fleet_signup_step")</th>
                                <th>@lang("$string_file.registered_date")</th>
                                <th>@lang("$string_file.action")</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fleetPendingDrivers as $index => $fleetDriver)
                                @php $driver = $fleetDriver->Driver; @endphp
                                <tr>
                                    <td>{{ $fleetPendingDrivers->firstItem() + $index }}</td>
                                    <td>
                                        {{$fleetDriver->Fleet->FleetName}}
                                    </td>
                                    <td>
                                        {{ $driver->first_name ?? '—' }} {{ $driver->last_name ?? '' }}
                                    </td>

                                    <td>{{ $driver->email ?? '—' }}</td>

                                    <td>{{ $driver->phoneNumber ?? '—' }}</td>

                                    <td>
                                        @if($fleetDriver->fleet_signup_step == 1)
                                            <span class="badge badge-warning">Either Driver Personal Document or Registration Step Not Completed</span>
                                        @elseif($fleetDriver->fleet_signup_step == 2)
                                            <span class="badge badge-info">Approval Pending From Admin</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ isset($fleetDriver->created_at) ? $fleetDriver->created_at->format('d M Y, h:i A') : '—' }}
                                    </td>

                                    <td>
                                        {{-- Approve --}}
                                        {{--<button type="button"
                                                class="btn btn-sm btn-success btn-approve"
                                                data-id="{{ $fleetDriver->id }}"
                                                data-name="{{ $driver->first_name ?? '' }}"
                                                data-action="{{ route('merchant.fleet-driver.approve', $fleetDriver->id) }}">
                                            <i class="fa fa-check"></i> @lang("$string_file.approve")
                                        </button>--}}
                            
                                        {{-- Reject --}}
                                        {{--<button type="button"
                                                class="btn btn-sm btn-danger btn-reject"
                                                data-id="{{ $fleetDriver->id }}"
                                                data-name="{{ $driver->first_name ?? '' }}"
                                                data-action="{{ route('merchant.fleet-driver.reject', $fleetDriver->id) }}">
                                            <i class="fa fa-times"></i> @lang("$string_file.reject")
                                        </button>--}}
                                        @if($fleetDriver->fleet_signup_step != 1)
                                            <a href="{{ route('merchant.fleet-driver.show',['id'=>$driver->id,'fdriver_id'=> $fleetDriver->id]) }}"
                                               class="btn btn-sm btn-info menu-icon btn_detail action_btn"><span
                                                        class="fa fa-list-alt"
                                                        title="View Driver Documents"></span></a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fa fa-inbox fa-2x"></i><br>
                                        @lang("$string_file.no_pending_drivers")
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $fleetPendingDrivers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Approve Modal --}}
    {{--<div class="modal fade" id="approveModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fa fa-check-circle"></i> @lang("$string_file.approve_driver")</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>@lang("$string_file.approve_driver_confirm") <strong id="approveDriverName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <form id="approveForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang("$string_file.cancel")</button>
                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> @lang("$string_file.approve")</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa fa-times-circle"></i> @lang("$string_file.reject_driver")</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>@lang("$string_file.reject_driver_confirm") <strong id="rejectDriverName"></strong>?</p>
                    <div class="form-group mt-2">
                        <label>@lang("$string_file.reason") <span class="text-muted">(optional)</span></label>
                        <textarea id="rejectReason" rows="3" class="form-control"
                                  placeholder="@lang("$string_file.enter_reason")"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="rejectForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="reason" id="rejectReasonHidden">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang("$string_file.cancel")</button>
                        <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> @lang("$string_file.reject")</button>
                    </form>
                </div>
            </div>
        </div>
    </div>--}}
@endsection

@section('js')
<script>
    $(document).ready(function () {

        $(document).on('click', '.btn-approve', function () {
            $('#approveDriverName').text($(this).data('name'));
            $('#approveForm').attr('action', $(this).data('action'));
            $('#approveModal').modal('show');
        });

        $(document).on('click', '.btn-reject', function () {
            $('#rejectDriverName').text($(this).data('name'));
            $('#rejectForm').attr('action', $(this).data('action'));
            $('#rejectModal').modal('show');
        });

        $('#rejectForm').on('submit', function () {
            $('#rejectReasonHidden').val($('#rejectReason').val());
        });

        $('#rejectModal').on('hidden.bs.modal', function () {
            $('#rejectReason').val('');
        });
    });
</script>
@endsection
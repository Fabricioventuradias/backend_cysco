@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title"><i class="fa-money" aria-hidden="true"></i>
                        @lang("$string_file.transactions")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form action="{{ route('transaction.pg-report') }}" method="GET">
                        <div class="table_search row p-3">
                            <div class="col-md-2 active-margin-top">@lang("$string_file.search_by") :</div>
                            <div class="col-md-3 col-xs-12 form-group active-margin-top">
                                {!! Form::text('reference_id', isset($search_data['reference_id']) ? $search_data['reference_id'] : NULL,['class'=>'form-control','placeholder'=>trans("$string_file.reference_id")]) !!}
                            </div>
                            <div class="col-md-3 col-xs-12 form-group active-margin-top">
                                <select class="form-control" name="request_status">
                                    <option value="">@lang("$string_file.status")</option>
                                    <option value="1" @if(!empty($search_data['request_status']) && $search_data['request_status'] == 1) selected @endif>@lang("$string_file.pending")</option>
                                    <option value="2" @if(!empty($search_data['request_status']) && $search_data['request_status'] == 2) selected @endif>@lang("$string_file.success")</option>
                                    <option value="3" @if(!empty($search_data['request_status']) && $search_data['request_status'] == 3) selected @endif>@lang("$string_file.failed")</option>
                                </select>
                            </div>
                            <div class="col-md-1 col-xs-12 form-group active-margin-top">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                            </div>
                            <div class="col-md-1">
                                <a href="{{ route('transaction.pg-report') }}">
                                    <button class="btn btn-small btn-success" type="button"><i class="fa fa-refresh"></i></button>
                                </a>
                            </div>
                        </div>
                    </form>
                    <table class="display nowrap table table-hover table-stripedw-full" id="customDataTable" style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.driver")</th>
                            <th>@lang("$string_file.amount")</th>
                            <th>@lang("$string_file.reference")</th>
                            <th>@lang("$string_file.payment_transaction")</th>
                            <th>@lang("$string_file.status")</th>
                            <th>@lang("$string_file.created_at")</th>
                                <th>@lang("$string_file.action")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $transactions->firstItem() @endphp
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    @if($transaction->Driver)
                                        {{$transaction->Driver->first_name . " " . $transaction->Driver->last_name}}<br>
                                        {{$transaction->Driver->phoneNumber}}<br>
                                        {{$transaction->Driver->email}}
                                    @else
                                        --
                                    @endif
                                </td>
                                <td>{{ number_format((float) $transaction->amount, 2) }}</td>
                                <td>{{ $transaction->reference_id ?? '--' }}</td>
                                <td>{{ $transaction->payment_transaction_id ?? '--' }}</td>
                                <td>
                                    @if($transaction->request_status == 1)
                                        <span class="badge badge-warning">@lang("$string_file.pending")</span>
                                    @elseif($transaction->request_status == 2)
                                        <span class="badge badge-success">@lang("$string_file.success")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.failed")</span>
                                    @endif
                                </td>
                                <td>{!! convertTimeToUSERzone($transaction->created_at, $transaction->Driver->CountryArea->timezone,null,$transaction->Merchant) !!}</td>
                                    <td style="width:100px; float:left">
                                        @if($transaction->request_status == 1)
                                            <button type="button"
                                                    class="btn btn-sm btn-warning menu-icon btn-check-status"
                                                    data-id="{{ $transaction->id }}"
                                                    data-url-template="{{ route('transaction.check-mpesa-status', ':id') }}"
                                                    data-original-title="Check Status" data-toggle="tooltip" data-placement="top">
                                                <i class="fa fa-refresh"></i>
                                            </button>
                                        @endif
                                    </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                    <div class="modal fade" id="mpesaStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">M-Pesa Status Check</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div id="mpesaStatusLoading">Checking status...</div>
                                    <div id="mpesaStatusResult" style="display:none">
                                        <p><strong>Status:</strong> <span id="mpesaStatusBadge"></span></p>
                                        <p><strong>Message:</strong> <span id="mpesaStatusMessage"></span></p>
                                        <pre id="mpesaStatusRaw" style="max-height:250px; overflow:auto; background:#f5f5f5; padding:10px; font-size:12px;"></pre>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @include('merchant.shared.table-footer', ['table_data' => $transactions, 'data' => $search_data])
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
<script>
$(document).on('click', '.btn-check-status', function () {
    var id = $(this).data('id');
    var $row = $(this).closest('tr');
    var url = $(this).data('url-template').replace(':id', id);

    $('#mpesaStatusLoading').show();
    $('#mpesaStatusResult').hide();
    $('#mpesaStatusModal').modal('show');

    $.ajax({
        url: url,
        type: 'GET',
        success: function (res) {
            $('#mpesaStatusLoading').hide();
            $('#mpesaStatusResult').show();

            if (!res.success) {
                $('#mpesaStatusBadge').html('<span class="badge badge-secondary">Error</span>');
                $('#mpesaStatusMessage').text(res.message);
                return;
            }

            var badgeClass = res.status === 'success' ? 'badge-success'
                : res.status === 'fail' ? 'badge-danger'
                : 'badge-warning';

            $('#mpesaStatusBadge').html('<span class="badge ' + badgeClass + '">' + res.status.toUpperCase() + '</span>');
            $('#mpesaStatusMessage').text(res.message);
            $('#mpesaStatusRaw').text(JSON.stringify(res.response, null, 2));

            // reflect the resolved status in the row without a full reload
            if (res.status !== 'pending') {
                var statusCell = $row.find('td').eq(7); // status column index — adjust if you add/remove columns
                statusCell.html(badgeClass === 'badge-success'
                    ? '<span class="badge badge-success">Success</span>'
                    : '<span class="badge badge-danger">Failed</span>');
                $row.find('.btn-check-status').remove();
            }
        },
        error: function (xhr) {
            $('#mpesaStatusLoading').hide();
            $('#mpesaStatusResult').show();
            $('#mpesaStatusBadge').html('<span class="badge badge-secondary">Error</span>');
            $('#mpesaStatusMessage').text(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Request failed.');
        }
    });
});
</script>
@endsection
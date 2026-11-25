@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->view_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        @if($export_permission)
                        <a href="{{route('excel.customersupports')}}">
                            <button type="button" class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-toggle="tooltip">
                                <i class="wb-download" title="@lang("$string_file.export_excel")"></i>
                            </button>
                        </a>
                          @endif
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.customer_support")</h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="post" action="{{ route('merchant.customer_support.search') }}">
                        @csrf
                        <div class="table_search row">
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <select class="form-control" name="application"
                                            id="application">
                                        <option value="">--@lang("$string_file.application")--</option>
                                        <option value="2">@lang("$string_file.driver")</option>
                                        <option value="1">@lang("$string_file.user")</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="name"
                                           placeholder="@lang("$string_file.name")"
                                           class="form-control col-md-12 col-xs-12">
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12 form-group ">
                                <div class="input-group">
                                    <input type="text" id="" name="date"
                                           placeholder="@lang("$string_file.date")"
                                           class="form-control col-md-12 col-xs-12 datepickersearch"
                                           id="datepickersearch">
                                </div>
                            </div>
                            <div class="col-sm-2  col-xs-12 form-group ">
                                <button class="btn btn-primary" type="submit" name="seabt12"><i
                                            class="fa fa-search" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full"
                           style="width:100%">
                        <thead>
                        <th>@lang("$string_file.sn")</th>
                        <th>@lang("$string_file.application")</th>
                        <th>@lang("$string_file.details")</th>
                        {{--                        <th>@lang("$string_file.name")</th>--}}
                        {{--                        <th>@lang("$string_file.email")</th>--}}
                        {{--                        <th>@lang("$string_file.phone")</th>--}}
                        <th>@lang("$string_file.query")</th>
                        <th>@lang("$string_file.reply")</th>
                        <th>@lang("$string_file.created_at")</th>
                        <th>@lang("$string_file.is_checked")</th>
                        </thead>
                        <tbody>
                        @php $sr = $customer_supports->firstItem() @endphp
                        @foreach($customer_supports as $customer_support)
                            <tr>
                                <td>
                                    {{ $sr }}
                                </td>
                                <td>
                                    @if($customer_support->application == 1)
                                        @lang("$string_file.user")
                                    @else
                                        @lang("$string_file.driver")
                                    @endif
                                </td>
                                <td>
                                    <span class="long_text">
                                        {{ is_demo_data($customer_support->name, $customer_support->Merchant) }}<br>
                                        {{ is_demo_data($customer_support->email, $customer_support->Merchant) }}<br>
                                        {{ is_demo_data($customer_support->phone, $customer_support->Merchant) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="long_text" style="display:inline-block; max-width:900px; overflow-wrap:break-word; white-space:normal;">{{ $customer_support->query }}</span>
                                </td>
                                <td>
                                    @if($customer_support->email)
                                        <button type="button" class="btn btn-xs btn-info reply-btn"
                                                data-id="{{ $customer_support->id }}"
                                                data-email="{{ $customer_support->email }}"
                                                data-query="{{ $customer_support->query }}"
                                                data-toggle="modal" data-target="#replyModal">
                                            <i class="fa fa-reply"></i> Reply
                                        </button>
                                        @if($customer_support->replied_at)
                                            <br><small class="text-success">Replied</small>
                                        @endif
                                    @else
                                        <span class="text-muted">No email</span>
                                    @endif
                                </td>
                                <td>
                                    {!! convertTimeToUSERzone($customer_support->created_at, null, null, $customer_support->Merchant) !!}
                                </td>
                                <td>
                                    <input type="checkbox" class="support-check" data-id="{{ $customer_support->id }}" {{ $customer_support->is_checked == 1 ? 'checked' : '' }} />
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                        </tbody>
                    </table>
                    <div class="pagination1 float-right">{{ $customer_supports->links() }}</div>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'view_text'])

    <div class="modal fade" id="replyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reply to Customer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reply_customer_support_id">
                    <div class="form-group">
                        <label>Sending to:</label>
                        <div id="reply_to_email" class="font-weight-bold"></div>
                    </div>
                    <div class="form-group">
                        <label>Original query:</label>
                        <div id="reply_original_query" class="text-muted" style="white-space: pre-wrap;"></div>
                    </div>
                    <div class="form-group">
                        <label>Your reply:</label>
                        <textarea id="reply_message" class="form-control" rows="5" placeholder="Type your reply..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" id="replySendBtn" class="btn btn-primary">Send Reply</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
    
    $(document).on('change', '.support-check', function() {
        console.log('kk');
        var id = $(this).data('id');
        var checked = $(this).is(':checked') ? 1 : 0;
        console.log(id);
        console.log("AJAX will post to: {{ route('support.checkbox.update') }}");
        $.ajax({
            method: 'POST',
            url: "{{ route('support.checkbox.update') }}", // Create this route
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                id: id,
                checked: checked
            },
            success: function(response) {
                // Optionally show a message, highlight, etc.
            },
            error: function(xhr) {
                alert('Failed to update checkbox!');
            }
        });
    });
    
    $(document).on('click', '.reply-btn', function() {
        $('#reply_customer_support_id').val($(this).data('id'));
        $('#reply_to_email').text($(this).data('email'));
        $('#reply_original_query').text($(this).data('query'));
        $('#reply_message').val('');
    });

    $('#replySendBtn').on('click', function() {
        var id = $('#reply_customer_support_id').val();
        var reply = $('#reply_message').val().trim();
        var $btn = $(this);

        if (reply === '') {
            alert('Please enter a reply message.');
            return;
        }

        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            method: 'POST',
            url: "{{ route('customer_support.reply.send') }}",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                id: id,
                reply: reply
            },
            success: function(response) {
                $btn.prop('disabled', false).text('Send Reply');
                if (response.status) {
                    $('#replyModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || 'Failed to send reply.');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Send Reply');
                alert('Failed to send reply email!');
            }
        });
    });
    </script>
@endsection

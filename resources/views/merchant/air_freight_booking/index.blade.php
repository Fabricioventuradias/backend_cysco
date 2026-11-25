@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <div class="panel-heading">
                <div class="panel-actions">
                    
                </div>
                <h3 class="panel-title"><i class="fa fa-file-text-o" aria-hidden="true"></i>
                    @lang("$string_file.air_freight") @lang("$string_file.booking_management")
                </h3>
            </div>
            <div class="panel-body container-fluid">
                <table id="customDataTable" class="display nowrap table table-hover table-stripedw-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.ride_id")</th>
                            <th>@lang("$string_file.user_details")</th>
                            <th>@lang("$string_file.payment_method")</th>
                            <th>@lang("$string_file.bill_amount")</th>
                            <th>@lang("$string_file.date")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = $bookings->firstItem() @endphp
                        @foreach($bookings as $booking)
                            <tr>
                                <td>{{$sr}}</td>
                                <td>{{$booking->merchant_booking_id}}</td>
                                <td>
                                    <span class="long_text">
                                         {{ is_demo_data($booking->User->UserName, $booking->Merchant) }}<br>
                                         {{ is_demo_data($booking->User->UserPhone, $booking->Merchant) }}<br>
                                         {{ is_demo_data($booking->User->email, $booking->Merchant) }}
                                    </span>
                                </td>
                                <td>{{$booking->PaymentMethod->payment_method}}</td>
                                <td>{{$booking->User->CountryArea->Country->isoCode . $booking->final_amount_paid}}</td>
                                <td>{!! convertTimeToUSERzone($booking->created_at, $booking->User->CountryArea->timezone,null,$booking->Merchant) !!}</td>
                                <td>
                                    <a target="_blank" title="@lang("$string_file.air_freight_details")"
                                       href="{{ route('air_freight.booking.details',$booking->id) }}"
                                       class="btn btn-sm btn-info menu-icon btn_money action_btn"><span
                                                class="fa fa-info-circle"
                                                title="Booking Details"></span></a>
                                </td>
                            </tr>
                            @php $sr++  @endphp
                        @endforeach
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', ['table_data' => $bookings, 'data' => $data])
            </div>
        </div>
    </div>
</div>
@endsection

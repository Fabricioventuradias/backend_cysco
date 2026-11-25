@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <a href="{{ route("air_freight.bookings") }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="fa fa-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="fa-info-circle" aria-hidden="true"></i>
                        @lang("$string_file.ride_details")
                        #{{$booking->merchant_booking_id}}
                    </h3>
                </header>
                <div class="panel-body">
                    <div id="user-profile">
                        <div class="row">
                            <div class="col-md-4 col-xs-12">
                                <div class="card my-2 shadow">
                                    <div class="card-body ">
                                        <div class="col-md-4 col-sm-4" style="float:left;">
                                            <img height="80" width="80" class="rounded-circle"
                                                 src="@if ($booking->User->UserProfileImage) {{ get_image($booking->User->UserProfileImage,'user') }} @else {{ get_image(null,'user') }} @endif"
                                                 alt="img">

                                        </div>
                                        <div class="card-text col-md-8 col-sm-8 py-2" style="float:left;">
                                            <h5 class="user-name">{{ is_demo_data($booking->User->UserName, $booking->Merchant) }}</h5>
                                            <h6 class="user-job">{{ is_demo_data($booking->User->UserPhone, $booking->Merchant) }}</h6>
                                            <h6 class="user-location">{{ is_demo_data($booking->User->email, $booking->Merchant) }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8 col-xs-12 mt-20">
                                 <div class="row">
                                    <div class="col-md-6 col-sm-6 col-xs-12 mb-5 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="font-weight-500 text-info text-uppercase mb-1">
                                                    <i class="icon fa-money"></i>
                                                    @lang("$string_file.payment")</div>
                                                <div class="mb-0">
                                                    {{ $booking->PaymentMethod->payment_method  }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-6 col-xs-12 mb-5 py-2">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="font-weight-500 text-warning text-uppercase mb-1">
                                                    <i class="icon fa-comments fa-2x text-gray-300"></i>
                                                    @lang("$string_file.current_status")</div>
                                                <div class="mb-0">
                                                    @if(!empty($arr_booking_status))
                                                        {!! isset($arr_booking_status[$booking->booking_status]) ? $arr_booking_status[$booking->booking_status] : ""  !!}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-12 mt-20">
                                    <h5>@lang("$string_file.document_uploaded")</h5>
                                    <hr>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>@lang("$string_file.sn")</th>
                                                    <th>@lang("$string_file.document_name")</th>
                                                    <th>@lang("$string_file.document") </th>
                                                    <th>@lang("$string_file.document_number") </th>
                                                    <th>@lang("$string_file.status")</th>
                                                    <th>@lang("$string_file.uploaded_at")</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $doc_sn = 1 @endphp
                                                @foreach($documentData as $document)
                                                <tr>
                                                    <td class="text-center">
                                                        {{$doc_sn}}
                                                    </td>
                                                    <td class="text-center">
                                                        {{$document->Document->DocumentName}}
                                                    </td>
                                                    <td class="text-center">
                                                        @php
                                                         $p_doc = get_image($document->document_file, 'booking_document', $booking->merchant_id, true, false);
                                                        @endphp
                                                            <a target="_blank" href="{{ $p_doc }}">
                                                                <img src="{{ $p_doc }}" alt="avatar" style="width: 100px;height: 100px;">
                                                            </a>
                                                    </td>
                                                    <td class="text-center">{{$document->document_number}}</td>
                                                    <td class="text-center">
                                                        @switch($document->document_verification_status)
                                                                @case(1)
                                                                @lang("$string_file.verified")
                                                                @break
                                                                @case(2)
                                                                @lang("$string_file.pending_for_verification")
                                                                @break
                                                                @case(3)
                                                                @lang("$string_file.rejected")
                                                                @break
                                                                @case(4)
                                                                @lang("$string_file.expired")
                                                                @break
                                                            @endswitch
                                                    </td>
                                                    <td class="text-center">
                                                        {!! convertTimeToUSERzone($document->updated_at, $booking->User->CountryArea->timezone,null,$booking->Merchant) !!}
                                                    </td>
                                                    @php $doc_sn = $doc_sn+1; @endphp
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                            </div>
                        </div>
                        @if($booking->booking_status == 1001)
                                    <div class="float-right mt-10">
                                        <a href="{{ route('air_freight.booking.confirm',$booking->id) }}">
                                            <button class="btn btn-success float-right">@lang("$string_file.approve")</button>
                                        </a>
                                        <button class="btn btn-danger float-right mr-2" data-toggle="modal"
                                                data-target="#exampleModalCenterTemp">@lang("$string_file.reject")
                                        </button>
                                    </div>
                                @endif
                        
                    </div>
                </div>  
            </div>
        </div>
    </div>    
    <div class="modal fade" id="exampleModalCenterTemp" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalCenterTitleTemp" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form class="form-group" action="{{ route('air_freight.booking.reject') }}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalCenterTitle">@lang("$string_file.reject_driver")</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <input type="hidden" value="{{ $booking->id }}" name="booking_id">
                            
                            
                        </div>
                        <div class="row">
                            {!! Form::hidden('request_from','booking_details') !!}
                            <div class="col-md-12">
                                <textarea class="form-control" placeholder="@lang("$string_file.comments")" name="comment" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">@lang("$string_file.close")</button>
                        <button type="submit" class="btn btn-primary">@lang("$string_file.reject") </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
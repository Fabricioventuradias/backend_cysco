@extends('merchant.layouts.main')

@section('content')

<div class="page">

    <div class="page-content">

        <div class="panel panel-bordered">

            <header class="panel-heading">

                <div class="panel-actions">

                    <div class="btn-group float-right">

                        @if(Auth::user('merchant')->demo != 1)

                           <a href="javascript:void(0);" style="pointer-events: none;">
                                <button type="button"
                                    class="btn btn-icon btn-success float-right"
                                    style="margin:10px"
                                    disabled>
                                    <i class="fa fa-download"></i>
                                </button>
                            </a>

                        @endif

                        @if(!empty($info_setting) && $info_setting->view_text != "")

                            <button class="btn btn-icon btn-primary float-right"
                                    style="margin:10px"
                                    data-target="#examplePositionSidebar"
                                    data-toggle="modal"
                                    type="button">

                                <i class="wb-info ml-1 mr-1"></i>

                            </button>

                        @endif

                    </div>

                </div>

                <h3 class="panel-title">

                    <i class="icon wb-list" aria-hidden="true"></i>

                    Laundry Orders

                </h3>

            </header>

            <div class="panel-body container-fluid">

                <form method="GET"
                      action="{{ url()->current() }}">

                    <div class="row">

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>Order ID</label>

                                <input type="text"
                                       class="form-control"
                                       name="merchant_order_id"
                                       value="{{ request('merchant_order_id') }}">

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>User Name</label>

                                <input type="text"
                                       class="form-control"
                                       name="user_name"
                                       value="{{ request('user_name') }}"
                                       placeholder="Search User Name">

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>Laundry Store Name</label>

                                <input type="text"
                                       class="form-control"
                                       name="laundry_outlet_name"
                                       value="{{ request('laundry_outlet_name') }}"
                                       placeholder="Search Laundry Outlet">

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>Payment Status</label>

                                <select class="form-control"
                                        name="payment_status">

                                    <option value="">All</option>

                                    <option value="1"
                                        {{ request('payment_status') == 1 ? 'selected' : '' }}>

                                        Pending

                                    </option>

                                    <option value="2"
                                        {{ request('payment_status') == 2 ? 'selected' : '' }}>

                                        Paid

                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>Order Status</label>

                                <select class="form-control"
                                        name="order_status">

                                    <option value="">All</option>

                                    @foreach($order_status as $key => $status)

                                        <option value="{{ $key }}"
                                            {{ request('order_status') == $key ? 'selected' : '' }}>

                                            {{ $status }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>From Date</label>

                                <input type="date"
                                       class="form-control"
                                       name="from_date"
                                       value="{{ request('from_date') }}">

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>To Date</label>

                                <input type="date"
                                       class="form-control"
                                       name="to_date"
                                       value="{{ request('to_date') }}">

                            </div>

                        </div>

                        <div class="col-md-3">

                            <div class="form-group"
                                 style="margin-top:27px;">

                                <button type="submit"
                                        class="btn btn-primary">

                                    <i class="fa fa-search"></i>
                                    Search

                                </button>

                                <a href="{{ url()->current() }}"
                                   class="btn btn-danger">

                                    Reset

                                </a>

                            </div>

                        </div>

                    </div>

                </form>

                <hr>

                <div class="row">

                    <div class="col-lg-12"
                         id="ecommerceRecentOrder">

                        <div class="card card-shadow table-row">

                            <div class="card-block bg-white table-responsive">

                                <table id="customDataTable"
                                       class="display nowrap table table-hover table-striped w-full"
                                       style="width:100%">

                                    <thead>

                                    <tr>

                                        <th style="min-width:70px;">SN</th>

                                        <th style="min-width:120px;">Order ID</th>

                                        <th style="min-width:220px;">User Details</th>

                                        <th style="min-width:250px;">Laundry Store</th>

                                        <th style="min-width:120px;">Quantity</th>

                                        <th style="min-width:250px;">Payment Details</th>

                                        <th style="min-width:250px;">Pickup Address</th>

                                        <th style="min-width:150px;">Order Date</th>

                                        <th style="min-width:220px;">Delivery On</th>

                                        <th style="min-width:160px;">Current Status</th>

                                        <th style="min-width:250px;">Item Images</th>

                                        <th style="min-width:180px;">Created At</th>

                                    </tr>

                                    </thead>

                                    <tbody>

                                    @if(!empty($arr_orders) && count($arr_orders) > 0)

                                        @php
                                            $sr = $arr_orders->firstItem();
                                        @endphp

                                        @foreach($arr_orders as $order)

                                            @php

                                                $images = json_decode(
                                                    $order->order_item_images,
                                                    true
                                                );

                                                $drop_slot = json_decode(
                                                    $order->drop_date_time_slot,
                                                    true
                                                );

                                            @endphp

                                            <tr>

                                                <td>

                                                    {{ $sr }}

                                                </td>

                                                <td>

                                                    <strong>

                                                        #{{ $order->merchant_order_id }}

                                                    </strong>

                                                </td>

                                                <td>

                                                    <strong>

                                                        {{ optional($order->User)->first_name }}

                                                    </strong>

                                                    <br>

                                                    {{ optional($order->User)->UserPhone }}

                                                    <br>

                                                    {{ optional($order->User)->email }}

                                                </td>

                                                <td>

                                                    <strong>

                                                        {{ optional($order->LaundryOutlet)->full_name }}

                                                    </strong>

                                                    <br>

                                                    {{ optional($order->LaundryOutlet)->phone_number }}

                                                    <br>

                                                    {{ optional($order->LaundryOutlet)->email }}

                                                </td>

                                                <td>

                                                    {{ $order->quantity }}

                                                </td>

                                                <td>

                                                    Cart :
                                                    {{ $order->cart_amount }}

                                                    <br>

                                                    Tax :
                                                    {{ $order->tax }}

                                                    <br>

                                                    Discount :
                                                    {{ $order->discount_amount }}

                                                    <br>

                                                    <strong>

                                                        Total :
                                                        {{ $order->final_amount_paid }}

                                                    </strong>

                                                    <br><br>

                                                    @if($order->payment_status == 1)

                                                        <span class="badge badge-warning">

                                                            Pending

                                                        </span>

                                                    @elseif($order->payment_status == 2)

                                                        <span class="badge badge-success">

                                                            Paid

                                                        </span>

                                                    @else

                                                        <span class="badge badge-danger">

                                                            Failed

                                                        </span>

                                                    @endif

                                                </td>

                                                <td>

                                                    {{ $order->drop_location }}

                                                </td>

                                                <td>

                                                    {{ $order->order_date }}

                                                </td>

                                                <td>

                                                    @if(!empty($drop_slot))

                                                        <strong>

                                                            {{ $drop_slot['date'] ?? '' }}

                                                        </strong>

                                                        <br>

                                                        {{ $drop_slot['from_time'] ?? '' }}

                                                        -

                                                        {{ $drop_slot['to_time'] ?? '' }}

                                                    @else

                                                        N/A

                                                    @endif

                                                </td>

                                                <td style="text-align:center;">

                                                    @if($order->order_status == 1)

                                                        <span class="badge badge-primary">

                                                            Generated

                                                        </span>

                                                    @elseif($order->order_status == 2)

                                                        <span class="badge badge-info">

                                                            Accepted

                                                        </span>

                                                    @elseif($order->order_status == 3)

                                                        <span class="badge badge-danger">

                                                            Cancelled

                                                        </span>

                                                    @elseif($order->order_status == 4)

                                                        <span class="badge badge-warning">

                                                            Picked

                                                        </span>

                                                    @elseif($order->order_status == 5)

                                                        <span class="badge badge-success">

                                                            Processing

                                                        </span>

                                                    @else

                                                        <span class="badge badge-secondary">

                                                            Unknown

                                                        </span>

                                                    @endif

                                                </td>

                                                <td>

                                                    @if(!empty($images))

                                                        @foreach($images as $image)

                                                            @if(!empty($image))

                                                                <a href="{{ get_image($image,'laundry_order_items',$order->merchant_id) }}"
                                                                   target="_blank">

                                                                    <img src="{{ get_image($image,'laundry_order_items',$order->merchant_id) }}"
                                                                         width="60"
                                                                         height="60"
                                                                         style="margin:2px;border-radius:5px;object-fit:cover;">

                                                                </a>

                                                            @endif

                                                        @endforeach

                                                    @else

                                                        No Images

                                                    @endif

                                                </td>

                                                <td>

                                                    {{ $order->created_at }}

                                                </td>

                                            </tr>

                                            @php $sr++; @endphp

                                        @endforeach

                                    @else

                                        <tr>

                                            <td colspan="12"
                                                class="text-center">

                                                No Orders Found

                                            </td>

                                        </tr>

                                    @endif

                                    </tbody>

                                </table>

                                @include('merchant.shared.table-footer',[
                                    'table_data' => $arr_orders,
                                    'data' => []
                                ])

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@include('merchant.shared.info-setting',[
    'info_setting'=>$info_setting,
    'page_name'=>'view_text'
])

@endsection
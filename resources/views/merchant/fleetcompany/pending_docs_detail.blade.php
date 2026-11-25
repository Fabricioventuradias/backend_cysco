@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">

        @include('merchant.shared.errors-and-messages')

        {{-- Vehicle Information --}}
        <div class="panel panel-bordered">

            <header class="panel-heading">

                <div class="panel-actions">
                    <a href="{{ route('fleetcompany.pending-doc') }}"
                       class="btn btn-default btn-sm"
                       style="margin:10px">
                        <i class="fa fa-arrow-left"></i>
                        @lang("$string_file.back")
                    </a>
                </div>

                <h3 class="panel-title">
                    <i class="fa fa-car"></i>

                    {{ $driver_vehicle->VehicleType->VehicleTypeName ?? '' }}

                    —
                    {{ $driver_vehicle->vehicle_number ?? 'No plate' }}
                </h3>

            </header>

            <div class="panel-body">

                <div class="row">

                    <div class="col-md-3">
                        <strong>@lang("$string_file.fleet_company") :</strong>
                        {{ $driver_vehicle->Fleet->FleetName ?? '-' }}
                    </div>

                    <div class="col-md-3">
                        <strong>@lang("$string_file.driver") :</strong>

                        @if($driver_vehicle->Driver)

                            {{ $driver_vehicle->Driver->first_name }}
                            {{ $driver_vehicle->Driver->last_name }}

                        @else

                            <span class="badge badge-default">
                                @lang("$string_file.not_assigned")
                            </span>

                        @endif
                    </div>

                    {{--<div class="col-md-3">

                        <strong>@lang("$string_file.docs_completed") :</strong>

                        @if($driver_vehicle->docs_completed == 1)

                            <span class="badge badge-success">
                                @lang("$string_file.yes")
                            </span>

                        @else

                            <span class="badge badge-warning">
                                @lang("$string_file.no")
                            </span>

                        @endif

                    </div>

                    <div class="col-md-3">

                        <strong>@lang("$string_file.fleet_ready") :</strong>

                        @if($driver_vehicle->is_fleet_ready == 1)

                            <span class="badge badge-success">
                                @lang("$string_file.ready")
                            </span>

                        @else

                            <span class="badge badge-danger">
                                @lang("$string_file.not_ready")
                            </span>

                        @endif

                    </div>--}}

                </div>

            </div>

        </div>

        {{-- Documents Table --}}
        <div class="panel panel-bordered">

            <header class="panel-heading">

                <h3 class="panel-title">
                    <i class="fa fa-file-text"></i>
                    @lang("$string_file.documents")
                </h3>

            </header>

            <div class="panel-body">

                <div class="table-responsive">

                    <table class="table table-hover table-bordered">

                        <thead style="background:#f7f7f7;">

                        <tr>

                            <th>#</th>

                            <th>@lang("$string_file.document")</th>

                            <th>@lang("$string_file.preview")</th>

                            <th>@lang("$string_file.document_number")</th>

                            <th>@lang("$string_file.expire_date")</th>

                            <th>@lang("$string_file.status")</th>

                            <th>@lang("$string_file.uploaded_at")</th>

                            <th width="180">
                                @lang("$string_file.action")
                            </th>

                        </tr>

                        </thead>

                        <tbody>

                        @forelse($driver_vehicle->DriverVehicleDocument as $key => $doc)

                            @php

                                $status = $doc->document_verification_status;

                                $badgeClass = match($status) {
                                    1 => 'warning',
                                    2 => 'success',
                                    3 => 'danger',
                                    default => 'default'
                                };

                                $statusLabel = match($status) {
                                    1 => trans("$string_file.pending_for_verification"),
                                    2 => trans("$string_file.approved"),
                                    3 => trans("$string_file.rejected"),
                                    default => trans("$string_file.pending")
                                };

                                $merchant_id = $driver_vehicle->Fleet->merchant_id ?? null;
        
                                $img_url = get_image($doc->document,'vehicle_document',$merchant_id, true, false);

                            @endphp

                            <tr>

                                <td>
                                    {{ $key + 1 }}
                                </td>

                                <td>

                                    <strong>
                                        {{ $doc->Document->DocumentName ?? '-' }}
                                    </strong>

                                </td>

                                <td>

                                    @if($img_url)

                                        <a href="{{ $img_url }}"
                                           target="_blank">

                                            <img src="{{ $img_url }}"
                                                 style="
                                                    width:70px;
                                                    height:70px;
                                                    object-fit:cover;
                                                    border-radius:4px;
                                                    border:1px solid #ddd;
                                                 ">

                                        </a>

                                    @else

                                        <span class="text-muted">
                                            @lang("$string_file.no_document")
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    {{ $doc->document_number ?? '-' }}

                                </td>

                                <td>

                                    @if($doc->expire_date)

                                        @if($doc->expire_date < date('Y-m-d'))

                                            <span class="badge badge-danger">
                                                {{ $doc->expire_date }}
                                            </span>

                                        @else

                                            <span class="badge badge-success">
                                                {{ $doc->expire_date }}
                                            </span>

                                        @endif

                                    @else

                                        -

                                    @endif

                                </td>

                                <td>

                                    <span class="badge badge-{{ $badgeClass }}">
                                        {{ $statusLabel }}
                                    </span>

                                </td>

                                <td>

                                    {{ date('d M Y, h:i A', strtotime($doc->created_at)) }}

                                </td>

                                <td>

                                    @if($status == 1)

                                        <div style="display:flex; gap:5px;">

                                            {{-- Approve --}}
                                            <form action="{{ route('fleetcompany.approve-doc') }}"
                                                  method="POST">

                                                @csrf

                                                <input type="hidden"
                                                       name="doc_id"
                                                       value="{{ $doc->id }}">

                                                <input type="hidden"
                                                       name="status"
                                                       value="2">

                                                <input type="hidden"
                                                       name="driver_vehicle_id"
                                                       value="{{ $driver_vehicle->id }}">

                                                <button type="submit"
                                                        class="btn btn-success btn-xs"
                                                        onclick="return confirm('@lang("$string_file.approve_confirmation")')">

                                                    <i class="fa fa-check"></i>

                                                    @lang("$string_file.approve")

                                                </button>

                                            </form>

                                            {{-- Reject --}}
                                            <form action="{{ route('fleetcompany.approve-doc') }}"
                                                  method="POST">

                                                @csrf

                                                <input type="hidden"
                                                       name="doc_id"
                                                       value="{{ $doc->id }}">

                                                <input type="hidden"
                                                       name="status"
                                                       value="3">

                                                <input type="hidden"
                                                       name="driver_vehicle_id"
                                                       value="{{ $driver_vehicle->id }}">

                                                <button type="submit"
                                                        class="btn btn-danger btn-xs"
                                                        onclick="return confirm('@lang("$string_file.reject_confirmation")')">

                                                    <i class="fa fa-times"></i>

                                                    @lang("$string_file.reject")

                                                </button>

                                            </form>

                                        </div>

                                    @else

                                        <span class="text-muted">-</span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8"
                                    class="text-center text-muted">

                                    @lang("$string_file.no_documents_uploaded")

                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
</div>
@endsection
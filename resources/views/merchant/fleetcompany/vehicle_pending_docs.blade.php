@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-file-text" aria-hidden="true"></i>
                    @lang("$string_file.pending_vehicle_documents")
                    <span class="badge badge-warning ml-2">{{ $pending_vehicles->total() }}</span>
                </h3>
            </header>
            <div class="panel-body container-fluid">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang("$string_file.fleet_company")</th>
                            <th>@lang("$string_file.vehicle")</th>
                            <th>@lang("$string_file.driver")</th>
                            <th>@lang("$string_file.pending")</th>
                            <th>@lang("$string_file.approved")</th>
                            <th>@lang("$string_file.rejected")</th>
                            <th>@lang("$string_file.action")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pending_vehicles as $dv)
                        @php
                            $docs     = $dv->DriverVehicleDocument;
                            $pending  = $docs->where('document_verification_status', 1)->count();
                            $approved = $docs->where('document_verification_status', 2)->count();
                            $rejected = $docs->where('document_verification_status', 3)->count();
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $dv->Fleet->FleetName ?? '-' }}</td>
                            <td>
                                {{ $dv->VehicleType->VehicleTypeName ?? '-' }}
                                <br>
                                <small class="text-muted">{{ $dv->vehicle_number ?? 'No plate' }}</small>
                            </td>
                            <td>
                                @if($dv->Driver)
                                    {{ $dv->Driver->first_name }} {{ $dv->Driver->last_name }}
                                @else
                                    <span class="badge badge-default">@lang("$string_file.not_assigned")</span>
                                @endif
                            </td>
                            <td>
                                @if($pending > 0)
                                    <span class="badge badge-warning">{{ $pending }} @lang("$string_file.pending")</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($approved > 0)
                                    <span class="badge badge-success">{{ $approved }} @lang("$string_file.approved")</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($rejected > 0)
                                    <span class="badge badge-danger">{{ $rejected }} @lang("$string_file.rejected")</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('fleetcompany.pending-doc-detail', $dv->id) }}"
                                   class="btn btn-sm btn-info">
                                    <i class="fa fa-eye"></i> @lang("$string_file.view_docs")
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                @lang("$string_file.no_pending_documents")
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @include('merchant.shared.table-footer', [
                    'table_data' => $pending_vehicles,
                    'data'       => []
                ])
            </div>
        </div>
    </div>
</div>
@endsection
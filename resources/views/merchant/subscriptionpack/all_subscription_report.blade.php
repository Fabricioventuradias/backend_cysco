@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')

        <div class="panel panel-bordered">
            {!! Form::open(['name' => '', 'url' => route('merchant.subscription.report.all'), 'method' => 'GET']) !!}
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-files-o" aria-hidden="true"></i>
                    @lang("$string_file.subscription") @lang("$string_file.report")
                </h3>
            </div>

            <div class="panel-body container-fluid table-responsive">

                {{-- ===== Filters ===== --}}
                <div class="table_search row">
                    <div class="col-md-4 col-xs-12 form-group active-margin-top">
                        <div class="input-daterange" data-plugin="datepicker">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="icon wb-calendar" aria-hidden="true"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control" name="start"
                                       value="{{ request('start') }}" placeholder="Start Date"/>
                            </div>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">to</span>
                                </div>
                                <input type="text" class="form-control" name="end"
                                       value="{{ request('end') }}" placeholder="End Date"/>
                            </div>
                        </div>
                    </div>

                    @if($show_vehicle_filter)
                        <div class="col-md-2 col-xs-12 form-group active-margin-top">
                            <select class="form-control" name="vehicletype" id="vehicletype">
                                <option value="">--@lang("$string_file.vehicle_type")--</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}"
                                            {{ request('vehicletype') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->VehicleTypeName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-2 col-xs-12 form-group active-margin-top">
                        <select class="form-control" name="entries" id="entries">
                            @foreach([20, 50, 100, 150, 200] as $opt)
                                <option value="{{ $opt }}" {{ request('entries') == $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-sm-2 col-xs-12 form-group active-margin-top">
                        <button class="btn btn-primary" type="submit">
                            <i class="fa fa-search" aria-hidden="true"></i>
                        </button>
                        <a href="{{ route('merchant.subscription.report') }}">
                            <button class="btn btn-success" type="button">
                                <i class="fa fa-refresh" aria-hidden="true"></i>
                            </button>
                        </a>
                    </div>
                </div>
                {!! Form::close() !!}

                {{-- ===== Table ===== --}}
                <table id="customDataTable" class="display nowrap table table-hover table-striped w-full" style="width:100%">
                    <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.subscription") @lang("$string_file.id")</th>
                            <th>@lang("$string_file.driver") @lang("$string_file.name")</th>
                            <th>@lang("$string_file.driver") @lang("$string_file.phone")</th>
                            @if($show_vehicle_filter)
                                <th>@lang("$string_file.vehicle_type")</th>
                            @endif
                            <th>@lang("$string_file.package") @lang("$string_file.name")</th>
                            <th>@lang("$string_file.package") @lang("$string_file.type")</th>
                            <th>@lang("$string_file.price")</th>
                            <th>@lang("$string_file.trips") (@lang("$string_file.used")/@lang("$string_file.total"))</th>
                            <th>@lang("$string_file.start") @lang("$string_file.date")</th>
                            <th>@lang("$string_file.end") @lang("$string_file.date")</th>
                            <th>@lang("$string_file.days") @lang("$string_file.left")</th>
                            <th>@lang("$string_file.status")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drivers as $key => $driver)
                            @php
                                $sub = $driver->DriverSubscriptionRecord->first();

                                $daysLeft = null;
                                if ($sub && !empty($sub->end_date_time)) {
                                    $end = \Carbon\Carbon::parse($sub->end_date_time);
                                    $daysLeft = $end->isFuture()
                                        ? (int) \Carbon\Carbon::now()->diffInDays($end, false)
                                        : 0;
                                }
                            @endphp
                            <tr>
                                <td>{{ ($drivers->currentPage() - 1) * $drivers->perPage() + $key + 1 }}</td>
                                <td>{{ $sub->id ?? '-' }}</td>
                                <td>
                                    {{ trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? '')) ?: '-' }}
                                </td>
                                <td>
                                      {{$driver->phoneNumber }}
                                </td>
                                @if($show_vehicle_filter)
                                    <td>{{ optional($sub ? $sub->VehicleType : null)->VehicleTypeName ?? '-' }}</td>
                                @endif
                                <td>{{ optional($sub ? $sub->SubscriptionPackage : null)->Name ?? '-' }}</td>
                                <td>
                                    @if($sub && $sub->package_type == 1)
                                        <span class="badge badge-info">@lang("$string_file.free")</span>
                                    @elseif($sub && $sub->package_type == 2)
                                        <span class="badge badge-primary">@lang("$string_file.paid")</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $merchant->Currency->code ?? '' }}
                                    {{ number_format($sub->price ?? 0, 2) }}
                                </td>
                                <td>
                                    @if($sub)
                                        {{ $sub->used_trips ?? 0 }} / {{ $sub->package_total_trips ?? 0 }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {!! !empty($sub->start_date_time) ? convertTimeToUSERzone($sub->start_date_time, optional($driver->CountryArea)->timezone, null, $merchant) : '-' !!}
                                </td>
                                <td>
                                    {!! !empty($sub->end_date_time) ? convertTimeToUSERzone($sub->end_date_time, optional($driver->CountryArea)->timezone, null, $merchant) : '-' !!}
                                </td>
                                <td>
                                    @if(!is_null($daysLeft))
                                        <span class="badge {{ $daysLeft <= 3 ? 'badge-warning' : 'badge-info' }}">
                                            {{ $daysLeft }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-success">@lang("$string_file.active")</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center">
                                    @lang("$string_file.no_data_found")
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex justify-content-end mt-3">
                    {{ $drivers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
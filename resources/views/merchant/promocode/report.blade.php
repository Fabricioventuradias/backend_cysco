@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content container-fluid">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title"><i class="fa-percent" aria-hidden="true"></i>
                        @lang("$string_file.promo_code") @lang("$string_file.report")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <table id="customDataTable" class="display nowrap table table-hover table-striped w-full"
                           style="width:100%">
                        <thead>
                        <tr>
                            <th>@lang("$string_file.sn")</th>
                            <th>@lang("$string_file.promo_code")</th>
                            <th>@lang("$string_file.service_area")</th>
                            <th>@lang("$string_file.segment")</th>
                            <th>@lang("$string_file.limit")</th>
                            <th>@lang("$string_file.used_promocode")</th>
                            <th>Remaining</th>
                            <th>@lang("$string_file.status")</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $sr = $promocodes->firstItem() @endphp
                        @foreach($promocodes as $promocode)
                            @php
                                $usedPromocode = \App\Models\Booking::where('promo_code', $promocode->id)->count();
                                $limit = (int) $promocode->promo_code_limit;
                                $remaining = $limit > 0 ? max($limit - $usedPromocode, 0) : "---";
                            @endphp
                            <tr>
                                <td>{{ $sr }}</td>
                                <td>
                                    {{ $promocode->promoCode }}
                                    @if(!empty($promocode->LanguageSingle))
                                        <br><small class="text-muted">{{ $promocode->LanguageSingle->promo_code_name }}</small>
                                    @endif
                                </td>
                                <td>{{ !empty($promocode->country_area_id) ? $promocode->CountryArea->CountryAreaName : "" }}</td>
                                <td>{{ ($promocode->segment_id != "") ? $segment_list[$promocode->segment_id] : "---" }}</td>
                                <td>{{ $limit > 0 ? $limit : "---" }}</td>
                                <td>{{ $usedPromocode }}</td>
                                <td>
                                    @if($remaining === "---")
                                        <span class="text-muted">---</span>
                                    @elseif($remaining == 0)
                                        <span class="badge badge-danger">0</span>
                                    @else
                                        <span class="badge badge-success">{{ $remaining }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($promocode->promo_code_status == 1)
                                        <span class="badge badge-success">@lang("$string_file.active")</span>
                                    @else
                                        <span class="badge badge-danger">@lang("$string_file.inactive")</span>
                                    @endif
                                </td>
                            </tr>
                            @php $sr++ @endphp
                        @endforeach
                        </tbody>
                    </table>
                    @include('merchant.shared.table-footer', ['table_data' => $promocodes, 'data' => []])
                </div>
            </div>
        </div>
    </div>
@endsection
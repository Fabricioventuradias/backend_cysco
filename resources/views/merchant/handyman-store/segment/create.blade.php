@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')

        <div class="panel panel-bordered">

            <header class="panel-heading">
                <div class="panel-actions">
                    {{-- Check All toggle --}}
                    <div class="pull-right" style="margin-top:8px; margin-right:10px;">
                        <label style="font-weight:500; cursor:pointer;">
                            <input type="checkbox" id="check_all" style="margin-right:5px;">
                            @lang("$string_file.check_all")
                        </label>
                    </div>
                </div>
                <h3 class="panel-title">
                    <i class="fa fa-tags" aria-hidden="true"></i>
                    @lang("$string_file.assign_segments") — {{ $store->full_name }}
                </h3>
            </header>

            <div class="panel-body container-fluid">

                {!! Form::open([
                    'url'    => route('merchant.handyman-store.segment.store', $store->id),
                    'method' => 'POST',
                    'id'     => 'segment_form',
                ]) !!}

                {{-- ── Store info banner ── --}}
                <div class="row p-3 mb-20 bg-blue-grey-100 ml-5 mr-5" style="border-radius:6px;">
                    <div class="col-md-2">
                        @if(!empty($store->business_logo))
                            <img src="{{ get_image($store->business_logo, 'handyman_store_logo', $store->merchant_id) }}"
                                 style="width:60px; height:60px; object-fit:cover; border-radius:50%;">
                        @endif
                    </div>
                    <div class="col-md-10">
                        <strong>{{ $store->full_name }}</strong><br>
                        <small class="text-muted">
                            @lang("$string_file.total_segments_assigned") :
                            <span id="selected_count" class="badge badge-primary">
                                {{ count($assigned_ids) }}
                            </span>
                            / {{ $all_segments->count() }}
                        </small>
                    </div>
                </div>

                {{-- ── Segments heading ── --}}
                <h5 class="mt-20 mb-15" style="border-bottom: 2px solid #e8e8e8; padding-bottom:8px;">
                    <i class="fa fa-list"></i>
                    @lang("$string_file.segments")
                </h5>

                @if($all_segments->isEmpty())
                    <div class="alert alert-warning">
                        @lang("$string_file.no_segments_found")
                    </div>
                @else
                    <div class="row ml-5 mr-5">
                        @foreach($all_segments as $segment)
                        
                            <div class="col-md-3 col-sm-6 mb-15">
                                <div class="segment-card {{ in_array($segment['segment_id'], $assigned_ids) ? 'active-segment' : '' }}"
                                     id="card_{{ $segment['segment_id'] }}"
                                     style="border: 1px solid #ddd;
                                            border-radius: 8px;
                                            padding: 12px 15px;
                                            cursor: pointer;
                                            transition: all 0.2s;
                                            {{ in_array($segment['segment_id'], $assigned_ids) ? 'border-color:#5b5ec9; background:#f0f0ff;' : 'background:#fff;' }}">

                                    <label style="cursor:pointer; width:100%; margin:0; display:flex; align-items:center; gap:10px;">

                                        {{-- Segment icon --}}
                                        <span style="width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; background:#eee; border-radius:50%; overflow:hidden; flex-shrink:0;">
                                            {{--@if(!empty($segment['segment_icon']))
                                                <img src="{{ $segment['segment_icon'] }}"
                                                     style="width:28px; height:28px; object-fit:contain;">
                                            @else --}}
                                                <i class="fa fa-wrench" style="color:#999;"></i>
                                            {{--@endif --}}
                                        </span>

                                        {{-- Name + checkbox --}}
                                        <span style="flex:1; font-weight:500; font-size:13px;">
                                            {{ $segment['name'] ?? $segment['slag'] }}
                                        </span>

                                        <input
                                            type="checkbox"
                                            name="segments[]"
                                            value="{{ $segment['segment_id'] }}"
                                            class="segment-checkbox"
                                            style="width:16px; height:16px; cursor:pointer;"
                                            {{ in_array($segment['segment_id'], $assigned_ids) ? 'checked' : '' }}>
                                    </label>

                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ── Save button ── --}}
                <div class="row mt-20 ml-5 mr-5">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i>
                            @lang("$string_file.save")
                        </button>
                        <a href="{{ url()->previous() }}" class="btn btn-default ml-10">
                            <i class="fa fa-arrow-left"></i>
                            @lang("$string_file.back")
                        </a>
                    </div>
                </div>

                {!! Form::close() !!}

            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    /* ── Check All ── */
    $('#check_all').on('change', function () {
        var checked = $(this).is(':checked');
        $('.segment-checkbox').prop('checked', checked);
        $('.segment-card').each(function () {
            toggleCardStyle($(this), checked);
        });
        updateCount();
    });

    /* ── Individual checkbox ── */
    $(document).on('change', '.segment-checkbox', function () {
        var card    = $(this).closest('.segment-card');
        var checked = $(this).is(':checked');
        toggleCardStyle(card, checked);
        updateCount();

        // update check-all state
        var total   = $('.segment-checkbox').length;
        var checked_count = $('.segment-checkbox:checked').length;
        $('#check_all').prop('checked',       checked_count === total);
        $('#check_all').prop('indeterminate', checked_count > 0 && checked_count < total);
    });

    /* ── Click on card toggles checkbox ── */
    $('.segment-card').on('click', function (e) {
        if ($(e.target).is('input[type="checkbox"]')) return; // already handled
        var checkbox = $(this).find('.segment-checkbox');
        checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
    });

    /* ── Card visual style ── */
    function toggleCardStyle(card, isChecked) {
        if (isChecked) {
            card.css({ 'border-color': '#5b5ec9', 'background': '#f0f0ff' })
                .addClass('active-segment');
        } else {
            card.css({ 'border-color': '#ddd', 'background': '#fff' })
                .removeClass('active-segment');
        }
    }

    /* ── Update count badge ── */
    function updateCount() {
        $('#selected_count').text($('.segment-checkbox:checked').length);
    }

    /* ── Set initial check-all indeterminate state ── */
    $(document).ready(function () {
        var total         = $('.segment-checkbox').length;
        var checked_count = $('.segment-checkbox:checked').length;
        if (checked_count > 0 && checked_count < total) {
            $('#check_all').prop('indeterminate', true);
        } else if (checked_count === total && total > 0) {
            $('#check_all').prop('checked', true);
        }
    });
</script>
@endsection
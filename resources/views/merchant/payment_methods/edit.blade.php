@extends('merchant.layouts.main')
@php $has_pos_segments = collect($merchant_segment)->whereIn('sub_group_for_app', [1,2])->count() > 0; @endphp
@section('content')
    <div class="page">
        <div class="page-content">
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->edit_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('merchant.paymentMethod.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px"><i
                                        class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.payment_method") </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification"
                          enctype="multipart/form-data"
                          action="{{ route('merchant.paymentMethod.update',$payment->id) }}">
                        {{method_field('PUT')}}
                        @php $required = false; @endphp
                        @csrf
                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="firstName3">
                                            @lang("$string_file.name")<span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="payment_name"
                                               name="payment_name"
                                               value="@if(!empty($payment->MethodName($merchant->id))) {{ $payment->MethodName($merchant->id) }} @else {{ $payment->payment_method }} @endif"
                                               placeholder="" required>
                                        @if ($errors->has('payment_name'))
                                            <label class="danger">{{ $errors->first('payment_name') }}</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sequence">
                                           @lang("$string_file.sequence")
                                        </label>
                                        <input type="number" class="form-control" id="sequence"
                                               name="sequence" min="0"
                                               value="{{ old('sequence', $sequence ?? 0) }}"
                                               placeholder="0">
                                        @if ($errors->has('sequence'))
                                            <label class="danger">{{ $errors->first('sequence') }}</label>
                                        @endif
                                    </div>
                                </div>
                                @if($merchant_segment)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.segment")<span class="text-danger">*</span>
                                            </label>
                                                    <select class="form-control select2" name="segment_id[]" multiple id="segment_select">
                                                        @foreach($merchant_segment as $segment)
                                                            <option value="{{ $segment['segment_id'] }}"
                                                                {{ in_array($segment['segment_id'], $selected_segments) ? 'selected' : '' }}>
                                                                {{ $segment['slag'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                            @if ($errors->has('segment_id'))
                                                <label class="danger">{{ $errors->first('segment_id') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if(!empty($pos_module_enable) && $pos_module_enable == 1 && $has_pos_segments)
                                    <div class="col-md-12 mt-3">
                                        <div class="form-group">
                                            {{-- only show heading if at least one sub_group_for_app=2 segment is selected --}}
                                            @php
                                                $selected_pos_segments = collect($merchant_segment)
                                                    ->whereIn('segment_id', $selected_segments)
                                                    ->whereIn('sub_group_for_app', [1,2])
                                                    ->count();
                                            @endphp
                                    
                                            @if($selected_pos_segments > 0)
                                                <label><strong>POS / App Source</strong></label>
                                            @endif
                                    
                                            <div id="segment-source-container">
                                                @foreach($merchant_segment as $segment)
                                                    @if(in_array($segment['segment_id'], $selected_segments) && in_array($segment['sub_group_for_app'], [1, 2]))
                                                    <div class="row align-items-center mb-2">
                                                        <div class="col-md-2">
                                                            <span class="badge badge-info" style="font-size:13px; padding:6px 10px;">
                                                                {{ $segment['slag'] }}
                                                            </span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <select class="form-control" name="segment_source_{{ $segment['segment_id'] }}">
                                                                <option value="1" {{ (isset($selected_sources[$segment['segment_id']]) && $selected_sources[$segment['segment_id']] == '1') ? 'selected' : '' }}>📱 App</option>
                                                                <option value="2" {{ (isset($selected_sources[$segment['segment_id']]) && $selected_sources[$segment['segment_id']] == '2') ? 'selected' : '' }}>🖥️ POS</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @endif
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="">@lang("$string_file.image")
                                            <span class="text-danger">*</span>
                                            @if(!empty($icon))
                                                <a href="{{$icon}}"
                                                   target="_blank">@lang("$string_file.view")</a>
                                            @endif
                                        </label>
                                        <input type="file" class="form-control" id="image" name="p_icon_image"
                                                {{$required}}/>
                                        @if ($errors->has('image'))
                                            <label class="text-danger">{{ $errors->first('image') }}</label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <div class="form-actions float-right">
                            @if($edit_permission)
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-square-o"></i> @lang("$string_file.update")
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'edit_text'])
@endsection
@section('js')
<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select Segments",
        allowClear: true
    });
});

@if(!empty($pos_module_enable) && $pos_module_enable == 1 && $has_pos_segments)
$('#segment_select').on('change', function() {
    var selectedSegments = $(this).val() || [];
    var segmentData = @json(array_values($merchant_segment));
    var selectedSources = @json($selected_sources ?? []);
    console.log(selectedSegments,segmentData,selectedSources)
    var html = '';
    var hasPosSeg = false;

    selectedSegments.forEach(function(segmentId) {
        var segment = segmentData.find(s => s.segment_id == segmentId);
        console.log('segmentId:', segmentId, 'found:', segment);
        if (segment && [1, 2].includes(parseInt(segment.sub_group_for_app))) {
            hasPosSeg = true;
            var currentSource = selectedSources[segmentId] ?? '1';
            html += '<div class="row align-items-center mb-2">';
            html += '<div class="col-md-2"><span class="badge badge-info" style="font-size:13px;padding:6px 10px;">' + segment.slag + '</span></div>';
            html += '<div class="col-md-3">';
            html += '<select class="form-control" name="segment_source_' + segmentId + '">';
            html += '<option value="1" ' + (currentSource == '1' ? 'selected' : '') + '>📱 App</option>';
            html += '<option value="2" ' + (currentSource == '2' ? 'selected' : '') + '>🖥️ POS</option>';
            html += '</select>';
            html += '</div></div>';
        }
    });

    $('#segment-source-container').html(html);

    // show/hide heading
    if (hasPosSeg) {
        $('#pos-source-heading').show();
    } else {
        $('#pos-source-heading').hide();
    }
});
@endif
</script>
@endsection

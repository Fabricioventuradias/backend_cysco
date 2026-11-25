@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->edit_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <a href="{{ route('countryareas.index') }}">
                            <button type="button" class="btn btn-icon btn-success float-right" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                    <h3 class="panel-title">
                        <i class="wb-user-plus" aria-hidden="true"></i>
                        @lang("$string_file.pos") {{$area->CountryAreaName}}
                    </h3>
                </header>

                <div class="panel-body container-fluid">
                    {!! Form::open([
                        'name'  => '',
                        'class' => 'steps-validation wizard-notification',
                        'id'    => 'country-area-pos-step',
                        'files' => true,
                        'url'   => route('countryareas.save.pos.step', $area->id)
                    ]) !!}
                    {!! Form::hidden('id', $area->id, ['id' => 'id']) !!}

                    <div class="row mt3">
                        <div class="col-md-12 mt-10">
                            <h5>
                                <i class="m-1 fa fa-shopping-cart"></i>
                                @lang("$string_file.pos") @lang("$string_file.service_type") @lang("$string_file.configuration")
                            </h5>
                        </div>
                    </div>

                    @if(!empty($arr_segment_services))
                        @foreach($arr_segment_services as $key => $segment)
                            <div class="border rounded p-4 mt-10 shadow-sm bg-light">
                                <div class="border rounded p-4 mb-2 bg-white">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <strong>{!! $segment['name'] !!}</strong>
                                                    </div>
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="row">
                                                        @foreach($segment['arr_services'] as $service)
                                                            @php
                                                                $service_type_id     = $service['id'];
                                                                $arr_selected        = isset($arr_selected_segment_service[$segment['segment_id']]) 
                                                                                        ? $arr_selected_segment_service[$segment['segment_id']] 
                                                                                        : [];
                                                                $checked             = in_array($service_type_id, $arr_selected) ? 'checked' : '';
                                                            @endphp
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <input 
                                                                        name="segment_service_type[{{ $segment['segment_id'] }}][]"
                                                                        value="{{ $service_type_id }}"
                                                                        id="service_{{ $service_type_id }}"
                                                                        class="form-group mr-20 mt-5 ml-20"
                                                                        type="checkbox"
                                                                        {{ $checked }}
                                                                    >
                                                                    {!! $service['locale_service_name'] !!}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning mt-10">
                            @lang("$string_file.no_pos_service_types_found")
                        </div>
                    @endif

                    <div class="form-actions d-flex flex-row-reverse p-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.save")
                            </button>
                    </div>

                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting', ['info_setting' => $info_setting, 'page_name' => 'edit_text'])
@endsection
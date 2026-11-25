@extends('merchant.layouts.main')
@section('content')
    <style>
        .impo-text {
            color: red;
            font-size: 15px;
            text-wrap: normal;
            display: none;
        }
    </style>
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('air_freight.air_stops') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                            
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-add-file" aria-hidden="true"></i>
                        {!! $air_stop['title'] !!}
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    {!! Form::open(['name'=>'','url'=>$air_stop['submit_url'],'class'=>'steps-validation wizard-notification']) !!}
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="stop_name">
                                    @lang("$string_file.name") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('stop_name',old('stop_name',isset($air_stop['data']->LanguageSingle->name) ? $air_stop['data']->LanguageSingle->name : ''),['id'=>'stop_name','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('stop_name'))
                                    <label class="text-danger">{{ $errors->first('stop_name') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="service_types">
                                    @lang("$string_file.service_type") :
                                    <span class="text-danger">*</span>
                                </label>
                                @php
                                    $service = "";
                                    if ($air_stop['data']->air_service_type_id == 1) {
                                        $service = trans("$string_file.charter") . ' (' . trans("$string_file.cargo") . ')';
                                    } elseif ($air_stop['data']->air_service_type_id == 2) {
                                        $service = trans("$string_file.charter") . ' (' . trans("$string_file.passengers") . ')';
                                    } elseif ($air_stop['data']->air_service_type_id == 3) {
                                        $service = trans("$string_file.consolidation") . ' (' . trans("$string_file.cargo_consolidation_local") . ')';
                                    } else {
                                        $service = trans("$string_file.consolidation") . ' (' . trans("$string_file.cargo_consolidation_international") . ')';
                                    }
                                @endphp
                                <input class="form-control service_type_id" value="{{ $service }}" readonly>
                                @if ($errors->has('service_type_id'))
                                    <label class="text-danger">{{ $errors->first('service_type_id') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="stop_name">
                                    @lang("$string_file.base_fare_amount") :
                                    <span class="text-danger">*</span>
                                </label>
                                {!! Form::text('base_fare_amount',old('base_fare_amount',isset($air_stop['data']->base_fare_amount) ? $air_stop['data']->base_fare_amount : ''),['id'=>'base_fare_amount','class'=>'form-control','required'=>true,'placeholder'=>'']) !!}
                                @if ($errors->has('base_fare_amount'))
                                    <label class="text-danger">{{ $errors->first('base_fare_amount') }}</label>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>@lang("$string_file.status") : <span class="text-danger">*</span></label>
                            <div class="form-group">
                                {!! Form::select('status',$air_stop['arr_status'],old('status',isset($air_stop['data']->status) ? $air_stop['data']->status:1),['id'=>'status','class'=>'form-control','required'=>true]) !!}
                                @if ($errors->has('status'))
                                    <label class="text-danger">{{ $errors->first('status') }}</label>
                                @endif

                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions float-right">
                            {!! Form::submit($air_stop['submit_button'],['class'=>'btn btn-primary','id'=>'']) !!}
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script type="text/javascript">
    </script>
@endsection

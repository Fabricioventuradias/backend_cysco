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
                            <a href="{{ route('merchant.air-freight-service-type') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                            
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-add-file" aria-hidden="true"></i>
                        @lang("$string_file.edit_service_type")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                   <form action="{{route('merchant.air-freight-service-type.save',$airfreightservices->id)}}" method="POST" enctype="multipart/form-data">
                       @csrf
                       <div class="row">
                           <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">@lang("$string_file.air_freight_type_name")
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="air_freight_type_name"
                                           id="air_freight_type_name"
                                           value="{{ !empty($airfreightservices->air_freight_type_name) ? $airfreightservices->air_freight_type_name : "" }}"
                                           class="form-control" placeholder="">
                                    @if ($errors->has('air_freight_type_name'))
                                        <label class="danger">{{ $errors->first('air_freight_type_name') }}</label>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">@lang("$string_file.air_service_type_name")
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="air_service_type_name"
                                           id="air_service_type_name"
                                           value="{{ !empty($airfreightservices->air_service_type_name) ? $airfreightservices->air_service_type_name : "" }}"
                                           class="form-control" placeholder="">
                                    @if ($errors->has('air_service_type_name'))
                                        <label class="danger">{{ $errors->first('air_service_type_name') }}</label>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Upload Image -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="air_service_type_image">@lang("$string_file.air_service_type_image") <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" id="air_service_type_image" name="air_service_type_image" <input type="file" class="form-control" id="air_service_type_image" name="air_service_type_image" @if(empty($airfreightservices->service_type_image)) required @endif>
                                        @if ($errors->has('air_service_type_image'))
                                            <span class="text-danger">{{ $errors->first('air_service_type_image') }}</span>
                                        @endif
                                    </div>
                                    
                                    @if(!empty($airfreightservices->service_type_image))
                                    <img src="{{ get_image($airfreightservices->service_type_image, 'air_service_type_image',$merchant_id)  }}" style="width:50%; height:50%; ">
                                    @endif
                                </div>
                            </div>
                       
                       <!-- Submit Button -->
                            <div class="form-group m-3 float-right">
                                <button type="submit" class="btn btn-primary">@lang("$string_file.save")</button>
                            </div>
                       
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script type="text/javascript">
    </script>
@endsection

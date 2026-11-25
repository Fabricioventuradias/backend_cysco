@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('merchant.weightparameter.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px">
                                    <i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                        @lang("$string_file.air_weight_parameter")
                        (@lang("$string_file.you_are_adding_in") {{ strtoupper(Config::get('app.locale')) }})
                    </h3>
                </div>
                @php $id = isset($weightparam->id) ? $weightparam->id : NULL; @endphp
                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" name="weight-param" id="weight-param"
                          action="{{route('merchant.weightparameter.save', $id)}}">
                        @csrf
                        {!! $service_html !!}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.air_freight_weight_param")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name"
                                           name="name"
                                           value="@if(!empty($weightparam->LanguageSingle)) {{ $weightparam->LanguageSingle->name }} @endif"
                                           placeholder=""
                                           required>
                                    @if ($errors->has('name'))
                                        <label class="text-danger">{{ $errors->first('name') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">
                                        @lang("$string_file.status")
                                        <span class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('status',$arr_status,old('status',isset($weightparam->status) ? $weightparam->status : NULL),['id'=>'status','class'=>'form-control','required'=>true]) !!}
                                    @if ($errors->has('status'))
                                        <label class="text-danger">{{ $errors->first('status') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sequence">
                                        @lang("$string_file.sequence")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="sequence" step="1" max="500"
                                           name="sequence" min="1"
                                           value="{{isset($weightparam->sequence) ? $weightparam->sequence : ""}}"
                                           placeholder="sequence"
                                           required>
                                    @if ($errors->has('sequence'))
                                        <label class="text-danger">{{ $errors->first('sequence') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price">
                                        @lang("$string_file.price")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="price" step="1"
                                           name="price" min="1"
                                           value="{{isset($weightparam->price) ? $weightparam->price : ""}}"
                                           placeholder="price"
                                           required>
                                    @if ($errors->has('price'))
                                        <label class="text-danger">{{ $errors->first('price') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName3">
                                        @lang("$string_file.description")
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="description"
                                              name="description" rows="2"
                                              placeholder="">@if(!empty($weightparam->LanguageSingle)) {{ $weightparam->LanguageSingle->description }} @endif</textarea>
                                    @if ($errors->has('description'))
                                        <label class="text-danger">{{ $errors->first('description') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions d-flex flex-row-reverse p-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.update")
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


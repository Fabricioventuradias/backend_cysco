@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right">
                            <a href="{{ route('merchant.category_air_service.index') }}">
                                <button type="button" class="btn btn-icon btn-success" style="margin:10px">
                                    <i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                        @lang("$string_file.category_air_service")
                    </h3>
                </div>

                @php
                    $id = isset($selected_category_id) ? $selected_category_id : NULL;
                @endphp

                <div class="panel-body container-fluid">
                    <form method="POST" class="steps-validation wizard-notification" name="weight-param" id="weight-param"
                          action="{{ route('merchant.category_air_service.save', $id) }}">
                        @csrf

                        <div class="row">
                            <!-- Air Freight Category -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang("$string_file.air_freight_category") <span class="text-danger">*</span></label>
                                    <select class="form-control" name="air_freight_category" id="air_freight_category" required>
                                        <option value="">@lang("$string_file.choose_category")</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ isset($selected_category_id) && $selected_category_id == $category->id ? 'selected' : '' }}>
                                                {{ $category->Name($category->merchant_id) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('air_freight_category')
                                        <label class="text-danger">{{ $message }}</label>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6>@lang("$string_file.air_weight_parameter") <span class="text-danger">*</span></h6>
                        <p class="text-muted">Pick the weight parameters that apply to each air service type. Leave a service type blank to exclude it entirely.</p>

                        <div class="row">
                            @foreach($airfreightservices as $service)
                                @php
                                    $isConsolidation = strtolower(trim($service->air_freight_type_name)) === 'consolidation';
                                    $selectedForThisService = $selected_service_weight_map[$service->id] ?? [];
                                    $isConsolidationSelected = in_array($service->id, $selected_consolidation_service_types ?? []);
                                @endphp
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ $service->air_freight_type_name }}({{ $service->air_service_type_name }})</label>
                        
                                        @if($isConsolidation)
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="consolidation_service_type[]" value="{{ $service->id }}" {{ $isConsolidationSelected ? 'checked' : '' }}>
                                                    Applicable for this category
                                                </label>
                                            </div>
                                            <p class="text-muted small">Weight/volume is entered per booking for consolidation, not selected here.</p>
                                        @else
                                            <select class="form-control select2" name="air_weight_parameter[{{ $service->id }}][]" multiple>
                                                @foreach($weightParams as $wp)
                                                    <option value="{{ $wp->id }}" {{ in_array($wp->id, $selectedForThisService) ? 'selected' : '' }}>
                                                        {{ $wp->AirFreightWeightParameterName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('air_weight_parameter')
                            <label class="text-danger">{{ $message }}</label>
                        @enderror

                        <!-- Submit Button -->
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
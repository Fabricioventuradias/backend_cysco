@extends('merchant.layouts.main')
@section('content')
@php
    $includedValues = old('included', isset($service->included) ? json_decode($service->included, true) : []);
    $excludedValues = old('excluded', isset($service->excluded) ? json_decode($service->excluded, true) : []);
@endphp
<div class="page">
    <div class="page-content">
        @include("merchant.shared.errors-and-messages")
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">
                    <div class="btn-group float-right">
                        <a href="{{ route('merchant.serviceType.index') }}">
                            <button type="button" class="btn btn-icon btn-success" style="margin:10px">
                                <i class="wb-reply"></i>
                            </button>
                        </a>
                    </div>
                </div>
                <h3 class="panel-title"><i class="wb-edit" aria-hidden="true"></i>
                    @lang("$string_file.service") (In @lang("$string_file.segment") : {{$segment}})</h3>
            </header>
            <div class="panel-body container-fluid">
                <form method="POST" class="steps-validation wizard-notification" enctype="multipart/form-data" action="{{ route('merchant.serviceType.update',isset($service->id) ? $service->id : NULL) }}">
                    {{method_field('PUT')}}
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.service_type")<span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="service" name="service" value="@if(isset($service->service_locale_name)) {{ $service->service_locale_name }} @endif" placeholder="" required>
                                @if ($errors->has('service'))
                                <label class="danger">{{ $errors->first('service') }}</label>
                                @endif
                                {!! Form::hidden('segment_id',$segment_id) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.description")
                                </label>
                                <input type="text" class="form-control" id="description" name="description" value="@if(isset($service->service_locale_description)) {{ $service->service_locale_description }} @endif" >
                                @if ($errors->has('description'))
                                <label class="danger">{{ $errors->first('description') }}</label>
                                @endif
                                {!! Form::hidden('segment_id',$segment_id) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="sequence">
                                    @lang("$string_file.sequence")<span class="text-danger">*</span>
                                </label> 
                                {!! Form::number('sequence',old('sequence',isset($service->MerchantServiceType[0]->pivot->sequence) ? $service->MerchantServiceType[0]->pivot->sequence : 1),['class'=>'form-control','required'=>true,'id'=>'sequence','min'=>0,'max'=>100]) !!}
                                @if ($errors->has('sequence'))
                                <label class="danger">{{ $errors->first('sequence') }}</label>
                                @endif
                            </div>
                        </div>
                        @if($segment_group_id == 2)
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="price_type">
                                        @lang("$string_file.price") @lang("$string_file.type")<span class="text-danger">*</span>
                                    </label>
                                    <select name="price_type" id="price_type" class="form-control">
                                        <option value="">Select Pricing Model</option>
                                        <option value="1" {{ old('price_type', $service->price_type ?? '') == 1 ? 'selected' : '' }}>Fixed</option>
                                        <option value="2" {{ old('price_type', $service->price_type ?? '') == 2 ? 'selected' : '' }}>Bidding</option>
                                        <option value="3" {{ old('price_type', $service->price_type ?? '') == 3 ? 'selected' : '' }}>Fixed + Hourly</option>
                                        <option value="4" {{ old('price_type', $service->price_type ?? '') == 4 ? 'selected' : '' }}>Fixed + Additional Charge</option>
                                        <option value="5" {{ old('price_type', $service->price_type ?? '') == 5 ? 'selected' : '' }}>Fixed + Site Visit</option>
                                    </select>
                                    @if ($errors->has('price_type'))
                                    <label class="danger">{{ $errors->first('price_type') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName3">
                                        @lang("$string_file.price") @lang("$string_file.type") @lang("$string_file.price_type_description")
                                    </label>
                                    <input type="text" class="form-control" id="price_type_description" name="price_type_description" value="@if(isset($service->price_type_description)) {{ $service->price_type_description }} @endif" >
                                    @if ($errors->has('price_type_description'))
                                    <label class="danger">{{ $errors->first('price_type_description') }}</label>
                                    @endif
                                    {!! Form::hidden('segment_id',$segment_id) !!}
                                </div>
                            </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.estimated_time") @lang("$string_file.inhour") 
                                </label>
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="wb-time" aria-hidden="true"></i></span>
                                    <input class="form-control"    id="estimated_time" autocomplete="off" min="0" required="" max="99999999" name="estimated_time" type="number" value="@if(isset($service->estimated_time)){{(int) $service->estimated_time }}@endif" >
                                </div>
                                @if ($errors->has('estimated_time'))
                                <label class="danger">{{ $errors->first('estimated_time') }}</label>
                                @endif
                               
                            </div>
                        </div>
                            
                            
                            <div class="row col-12">
                                <!-- INCLUDED -->
                                <div class="col-md-6">
                                    <label>
                                        @lang("$string_file.included")<span class="text-danger">*</span>
                                    </label>
                            
                                    <div id="included-wrapper">
                                        @if(!empty($includedValues))
                                            @foreach($includedValues as $index => $value)
                                                <div class="row included-row mb-2 align-items-end">
                                                    
                                                    <div class="col-md-8">
                                                        <input type="text" name="included[]" class="form-control"
                                                               value="{{ $value }}" placeholder="Enter Included Value">
                                                    </div>
                                                    <div class="col-md-2 d-flex">
                                                        <button type="button" class="btn btn-success w-100 add-row">+</button>
                                                    </div>
                                                    <div class="col-md-2 d-flex">
                                                        <button type="button" class="btn btn-danger w-100 remove-row"
                                                            {{ $index == 0 ? 'style=display:none;' : '' }}>-</button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <!-- Default empty row -->
                                            <div class="row included-row mb-2 align-items-end">
                                                <div class="col-md-8">
                                                    <input type="text" name="included[]" class="form-control"
                                                           placeholder="Enter Included Value">
                                                </div>
                                                <div class="col-md-2 d-flex">
                                                    <button type="button" class="btn btn-success w-100 add-row">+</button>
                                                </div>
                                                <div class="col-md-2 d-flex">
                                                    <button type="button" class="btn btn-danger w-100 remove-row" style="display:none;">-</button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            
                                <!-- EXCLUDED -->
                                <div class="col-md-6">
                                    <label>
                                        @lang("$string_file.excluded")<span class="text-danger">*</span>
                                    </label>
                                    <div id="excluded-wrapper">
                                        @if(!empty($excludedValues))
                                            @foreach($excludedValues as $index => $value)
                                                <div class="row excluded-row mb-2 align-items-end">
                                                    
                                                    <div class="col-md-8">
                                                        <input type="text" name="excluded[]" class="form-control"
                                                               value="{{ $value }}" placeholder="Enter Excluded Value">
                                                    </div>
                                    
                                                    <div class="col-md-2 d-flex">
                                                        <button type="button" class="btn btn-success w-100 add-exclude-row">+</button>
                                                    </div>
                                    
                                                    <div class="col-md-2 d-flex">
                                                        <button type="button" class="btn btn-danger w-100 remove-exclude-row"
                                                            {{ $index == 0 ? 'style=display:none;' : '' }}>-</button>
                                                    </div>
                                    
                                                </div>
                                            @endforeach
                                        @else
                                            <!-- Default empty row -->
                                            <div class="row excluded-row mb-2 align-items-end">
                                                <div class="col-md-8">
                                                    <input type="text" name="excluded[]" class="form-control"
                                                           placeholder="Enter Excluded Value">
                                                </div>
                                                <div class="col-md-2 d-flex">
                                                    <button type="button" class="btn btn-success w-100 add-exclude-row">+</button>
                                                </div>
                                                <div class="col-md-2 d-flex">
                                                    <button type="button" class="btn btn-danger w-100 remove-exclude-row" style="display:none;">-</button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            
                            </div>
                        @endif
                       
                        @if(($segment_id == 1 || $segment_id == 2) || $segment_id = 245 || $appConfig->show_recommended_services == 1)
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="icon">
                                    @lang("$string_file.icon")
                                    @if(isset($service->MerchantServiceType[0]->pivot->service_icon) && $service->MerchantServiceType[0]->pivot->service_icon != '')
                                    <a href="{{get_image($service->MerchantServiceType[0]->pivot->service_icon,'service')}}" target="_blank">@lang("$string_file.view")</a>
                                    
                                    @endif
                                </label>
                                <div class="row">
                                    <div class="col-md-9">
                                        <input type="file" class="form-control" id="icon" name="icon" placeholder="@lang("$string_file.icon")">
                                    </div>
                                    <div class="col-md-3">
                                        @if(isset($service['pivot']->service_icon) && $service['pivot']->service_icon != '')
                                            <a href="{{ route('merchant.serviceType.image.remove',$service['id']) }}" class="btn btn-icon btn-danger">@lang("$string_file.delete")</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="firstName3">
                                    @lang("$string_file.service") @lang("$string_file.images") 
                                </label>
                                 @php
                                    $already_uploaded = isset($service->images) && !empty($service->images) ? $service->images->count() : "0"; // Example: 2
                                    $max_upload = 4;
                                @endphp
                                <input type="file" class="form-control" id="banners" name="banners[]" placeholder="@lang("$string_file.banners")" multiple data-existing ="{{$already_uploaded}}" onchange = 'checkUploadLimit(this)'>
                                @if ($errors->has('banners'))
                                <label class="danger">{{ $errors->first('banners') }}</label>
                                @endif
                               
                            </div>
                        </div>
                        @if(isset($service->images) && !empty($service->images))
                                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-top:10px; align-items:center;">
                                    @foreach($service->images as $img)
                                        @php $url = get_image($img->image,'service_type_image',$service->owner_id); @endphp
                                        <div style="position:relative;border:1px solid #ddd;border-radius:8px;overflow:hidden;background:#fff;padding:1px;text-align:center;">
                                            <a href="{{ $url }}" target="_blank">
                                                <img src="{{ $url }}" style="width:100%;height:50px;object-fit:cover;border-radius:5px;">
                                            </a>
                                        
                                            <div style="position:absolute;top:5px;right:5px;display:flex;gap:5px;">
                                               <a href="#"
                                                   class="edit-image-btn"
                                                   data-id="{{ $img->id }}"
                                                   data-url="{{ $url }}"
                                                   data-toggle="modal"
                                                   data-target="#serviceImage"
                                                   style="position:absolute;top:5px;right:30px;background:#ffc107;color:#fff;border-radius:50%;padding:4px 6px;font-size:10px;">
                                                    <i class="icon wb-edit"></i>
                                                </a>
                                                <a href="{{ route('merchant.serviceType.delete.image', $img->id) }}" style="background:#ff4747;color:#fff;border-radius:50%;padding:4px 6px;font-size:10px;">
                                                    <i class="icon wb-trash"></i>
                                                </a>
                                        
                                            </div>
                                        
                                        </div>
                                    @endforeach
                                </div>
                        @endif
                    </div>
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

    <!--image update model-->
    <div class="modal fade" id="serviceImage" tabindex="-1" role="dialog" aria-labelledby="bookingDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document"> <!-- larger modal -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingDetailsLabel">Service Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="imageEditForm" method="POST" action="{{ route('merchant.serviceType.update.image') }}" enctype="multipart/form-data"> 
                        @csrf
                        <input type="hidden" name="image_id" id="image_id">
                        <div class="text-center mb-3">
                            <label>Current Image</label><br>
                            <img id="old_image_preview" src="" 
                                 style="width:120px;height:80px;object-fit:cover;border:1px solid #ddd;border-radius:5px;">
                        </div>
                        <div class="form-group">
                            <label>Upload New Image</label>
                            <input type="file" name="image" id="new_image" class="form-control">
                        </div>
                        <div class="text-center">
                            <img id="new_image_preview" 
                                 style="display:none;width:120px;height:80px;object-fit:cover;border:1px solid #ddd;border-radius:5px;">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
        <!--End Image model here -->



@endsection

@section('js')

<script>
$(document).ready(function () {

    // INCLUDED -------------------------

    $(document).on('click', '.add-row', function () {
        let row = $(this).closest('.included-row').clone();

        row.find('input').val('');
        row.find('.remove-row').show(); // show remove button for new rows

        $('#included-wrapper').append(row);
        toggleRemoveButtons('#included-wrapper', '.included-row', '.remove-row');
    });

    $(document).on('click', '.remove-row', function () {
        $(this).closest('.included-row').remove();
        toggleRemoveButtons('#included-wrapper', '.included-row', '.remove-row');
    });


    // EXCLUDED -------------------------

    $(document).on('click', '.add-exclude-row', function () {
        let row = $(this).closest('.excluded-row').clone();

        row.find('input').val('');
        row.find('.remove-exclude-row').show();

        $('#excluded-wrapper').append(row);
        toggleRemoveButtons('#excluded-wrapper', '.excluded-row', '.remove-exclude-row');
    });
    $(document).on('click', '.remove-exclude-row', function () {
        $(this).closest('.excluded-row').remove();
        toggleRemoveButtons('#excluded-wrapper', '.excluded-row', '.remove-exclude-row');
    });


    // COMMON FUNCTION ------------------

    function toggleRemoveButtons(wrapper, rowClass, btnClass) {
        let rows = $(wrapper).find(rowClass);

        if (rows.length === 1) {
            rows.find(btnClass).hide(); // hide if only one row
        } else {
            rows.find(btnClass).show();
            rows.first().find(btnClass).hide(); // first row no remove button
        }
    }
    
    // image model code here 
    
    $(document).on('click', '.edit-image-btn', function () {
        let id = $(this).data('id');
        let url = $(this).data('url');
    
        $('#image_id').val(id);
        $('#old_image_preview').attr('src', url);
    
        // reset new preview
        $('#new_image').val('');
        $('#new_image_preview').hide();
    });
    
    
    // Preview new selected image
    $('#new_image').on('change', function () {
        let file = this.files[0];
    
        if (file) {
            let reader = new FileReader();
    
            reader.onload = function (e) {
                $('#new_image_preview').attr('src', e.target.result).show();
            };
    
            reader.readAsDataURL(file);
        }
    });
    

});
function checkUploadLimit(input) {
        let existing = parseInt(input.dataset.existing); // Already uploaded
        
        let selected = input.files.length;
        let max = 4;
        
        if(existing == 4){
            alert("{{trans("$string_file.you_can_upload_only")}} " + max + " {{trans("$string_file.images")}}");
            input.value = ""; // reset file selection
        }
        if ((existing + selected) > max) {
            alert("{{trans("$string_file.you_can_upload_only")}} " + (max - existing) + " {{trans("$string_file.more_images")}}");
            input.value = ""; // reset file selection
        }
    }
</script>
@endsection

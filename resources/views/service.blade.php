<div class="row">
    <div class="col-md-2">
        <h3 class="panel-title" style="padding: 1px !important;">
            @lang("$string_file.service")<span class="text-danger">*</span>
        </h3>
    </div>
    <div class="col-md-10">
        @foreach($arr_segment as $key => $segment)
            @if(!empty($segment['arr_services']))
                @foreach($segment['arr_services'] as $service)
                    <label class="mr-15" style="display:inline-block;">
                        {{ $service['locale_service_name'] }}
                        <input name="service[]" value="{{ $service['id'] }}"
                               class="form-group mr-10 mt-5 ml-20 area_service"
                               type="checkbox"
                               @if(in_array($service['id'], $selected ?? [])) checked @endif>
                    </label>
                @endforeach
            @endif
        @endforeach

        @if ($errors->has('segment'))
            <span class="help-block">
                <strong>{{ $errors->first('segment') }}</strong>
            </span>
        @endif
    </div>
</div>
<hr>
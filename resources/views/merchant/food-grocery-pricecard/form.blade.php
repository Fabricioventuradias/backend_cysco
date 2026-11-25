@extends('merchant.layouts.main')
@section('content')
    @php $id = NULL; $mandatory_astrick = ""; $required=NULL;@endphp
    @if(isset($data['price_card']['id']))
        @php $id = $data['price_card']['id']; @endphp
    @endif

    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        @if(!empty($info_setting) && $info_setting->add_text != "")
                            <button class="btn btn-icon btn-primary float-right" style="margin:10px"
                                    data-target="#examplePositionSidebar" data-toggle="modal" type="button">
                                <i class="wb-info ml-1 mr-1" title="Info" style=""></i>
                            </button>
                        @endif
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('food-grocery.price_card',[$price_card_for]) }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title"><i class="icon wb-plus" aria-hidden="true"></i>
                        @lang("$string_file.add_price_card")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <section id="validation">
                        {!! Form::open(["id" => "food-grocery-form","class"=>"steps-validation wizard-notification","files"=>true,"url"=>route("food-grocery.price_card.save",$id)]) !!}
                        {!! Form::hidden('id',$id,array("id" => "id")) !!}
                        {!! Form::hidden('price_card_for',$price_card_for,array("id" => "price_card_for")) !!}
                        <fieldset>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="name">@lang("$string_file.service_area")<span
                                                    class="text-danger">*</span>
                                        </label>
                                        {!! Form::select('country_area_id',add_blank_option($data['arr_areas'],trans("$string_file.select")),old('country_area_id',isset($data['price_card']['country_area_id']) ? $data['price_card']['country_area_id'] :NULL),['class'=>'form-control','required'=>true,'id'=>'country_area_id','onChange'=>"getSegment()"]) !!}
                                        @if ($errors->has('country_area_id'))
                                            <span class="help-block">
                                                    <strong>{{ $errors->first('country_area_id') }}</strong>
                                                </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>@lang("$string_file.segment") <span class="text-danger">*</span>
                                        </label>
                                        <div class="form-group">
                                            {!! Form::select('segment_id',add_blank_option($data['arr_segment'],trans("$string_file.select")),old('segment_id',isset($data['price_card']['segment_id']) ? $data['price_card']['segment_id'] :NULL),["class"=>"form-control","id"=>"area_segment","required"=>true,'onChange'=>"getService()"]) !!}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>@lang("$string_file.service_type") <span
                                                    class="text-danger">*</span>
                                        </label>
                                        <div class="form-group">
                                            {!! Form::select('service_type_id',add_blank_option($data['arr_services'],trans("$string_file.select")),old('service_type_id',isset($data['price_card']['service_type_id']) ? $data['price_card']['service_type_id'] :NULL),['class'=>'form-control','required'=>true,'id'=>'service_type_id']) !!}
                                        </div>
                                    </div>
                                </div>
                                @if($price_card_for == 2)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.tax") (%)
                                                {{--<span class="text-danger">*</span>--}}
                                            </label>
                                            {!! Form::number('tax',old('tax',isset($data['price_card']['tax']) ?
                                            $data['price_card']['tax'] : ''),['class'=>'form-control','id'=>'tax',
                                            'placeholder'=>'','min'=>0, 'maxlength'=> 3,'step'=>$data['step_value']]) !!}
                                            @if ($errors->has('tax'))
                                                <label class="text-danger">{{ $errors->first('tax') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <input type="hidden" id="user_wallet_status" name="user_wallet_status" value="{{$configuration->user_wallet_status}}"/>
                                    @if($configuration->user_wallet_status == 1)
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="minimum_wallet_amount">
                                                    @lang("$string_file.minimum_wallet_amount")
                                                    <span class="text-danger">*</span>
                                                </label>
                                                {!! Form::number('minimum_wallet_amount',old('minimum_wallet_amount',isset($data['price_card']['minimum_wallet_amount']) ? $data['price_card']['minimum_wallet_amount'] : 0),["class"=>"form-control","id"=>"minimum_wallet_amount","placeholder"=>"","min"=>"0", "step"=>"0.01","required"=>true]) !!}
                                            </div>
                                        </div>
                                    @endif
                                @endif
                                @if($price_card_for == 1)
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.pickup_amount")
                                                <span class="text-danger">*</span>
                                            </label>
                                            {!! Form::number('pick_up_fee',old('pick_up_fee',isset($data['price_card']['pick_up_fee']) ? $data['price_card']['pick_up_fee'] : ''),['class'=>'form-control','id'=>'pick_up_fee','placeholder'=>'','required'=>true,'min'=>0,'step'=>$data['step_value']]) !!}
                                            @if ($errors->has('pick_up_fee'))
                                                <label class="text-danger">{{ $errors->first('pick_up_fee') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="firstName3">
                                                @lang("$string_file.drop_off_amount")
                                                <span class="text-danger">*</span>
                                            </label>
                                            {!! Form::number('drop_off_fee',old('drop_off_fee',isset
                                            ($data['price_card']['drop_off_fee']) ? $data['price_card']['drop_off_fee'] : ''),['class'=>'form-control','id'=>'drop_off_fee','placeholder'=>'','required'=>true,'min'=>0,'step'=>$data['step_value']]) !!}
                                            @if ($errors->has('drop_off_fee'))
                                                <label class="text-danger">{{ $errors->first('drop_off_fee') }}</label>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>@lang("$string_file.status") <span class="text-danger">*</span>
                                        </label>
                                        <div class="form-group">
                                            {!! Form::select('status',$data['arr_status'],old('status',isset($data['price_card']['status']) ? $data['price_card']['status'] :NULL),['class'=>'form-control','required'=>true,'id'=>'status']) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <h5 class=""><i class="icon fa-clock-o" aria-hidden="true"></i>
                            @lang("$string_file.cancel_order")
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name">@lang("$string_file.cancel_order")
                                        <span
                                                class="text-danger">*</span>
                                    </label>
                                    {!! Form::select('cancel_charges',array(1 => trans("$string_file.enable"), 2 => trans("$string_file.disable")),old('cancel_charges',isset($data['price_card']['cancel_charges']) ? $data['price_card']['cancel_charges'] :2),['class'=>'form-control','required'=>true,'id'=>'cancel_charges','onChange'=>"changeCancellation()"]) !!}
                                    @if ($errors->has('cancel_charges'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('cancel_charges') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang("$string_file.cancel_charges")
                                    </label>
                                    {!! Form::number('cancel_amount',old('cancel_amount',isset
                                    ($data['price_card']['cancel_amount']) ? $data['price_card']['cancel_amount'] :NULL),["class"=>"form-control","id"=>"cancel_amount",'min'=>0,'step'=>$data['step_value']]) !!}
                                    @if ($errors->has('cancel_amount'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('cancel_amount') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang("$string_file.free_minutes")
                                    </label>
                                    {!! Form::number('cancel_time',old('cancel_time',isset($data['price_card']['cancel_time']) ? $data['price_card']['cancel_time'] :NULL),["class"=>"form-control","id"=>"cancel_time"]) !!}
                                    @if($errors->has('cancel_amount'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('cancel_amount') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @if($configuration->night_charges == 1)
                        {{-- ================================================================ --}}
                        {{-- ============ NIGHT / TIME CHARGES (SLOT BASED) : NEW =========== --}}
                        {{-- ================================================================ --}}
                        <h5 class="form-section col-md-12" style="color: black"><i
                                    class="fa fa-moon-o"></i> @lang("$string_file.night_charges")
                        </h5>
                        <hr>
                        <div id="after-add-more">
                            @php $i=0; @endphp
                            @if(!empty($id) && isset($data['price_card']->ExtraCharges) && count($data['price_card']->ExtraCharges) > 0)
                                @foreach($data['price_card']->ExtraCharges as $key => $value)
                                    <div class="dynamic-copy">
                                        <div class="row" style="text-align: center;">
                                            <div class="col-md-9">
                                                <label for="weekdays">@lang("$string_file.select_week_days")
                                                    :<span class="text-danger">*</span></label>
                                                <div class="form-group">
                                                    <div class="weekDays-selector" index="{{$i}}">
                                                        @php $slot_week_days = explode(",",$value->slot_week_days);@endphp
                                                        @foreach($days as $key=>$day)
                                                            @php $checked =in_array($key,$slot_week_days) ? 'checked'  : ''; @endphp
                                                            @php $day_letter = substr($day, 0, 1); @endphp
                                                            <input type="checkbox" value="{!! $key !!}"
                                                                   name="week_days[{!! $i !!}][{!! $key !!}]"
                                                                   id="fg-weekday-{{$i}}-{{$key}}"
                                                                   class="weekday weekday{!! $i !!}" {!! $checked !!}>
                                                            <label for="fg-weekday-{{$i}}-{{$key}}">{!! $day_letter !!}</label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            @if($i==0)
                                                <div class="col-md-3">
                                                    <div class="input-group-btn">
                                                        <button class="btn btn-success add-more" type="button">
                                                            <i class="glyphicon glyphicon-plus"></i> @lang("$string_file.add")  @lang("$string_file.new")  @lang("$string_file.slots")
                                                        </button>
                                                        <button class="btn btn-primary float-right" id="reset_week"
                                                                type="button">
                                                            @lang("$string_file.reset")
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($i > 0)
                                                <div class="col-md-3">
                                                    <div class="input-group-btn">
                                                        <button class="btn btn-danger remove_loop remove" value="{{$i}}"
                                                                type="button"><i class="glyphicon glyphicon-remove"></i>
                                                            @lang("$string_file.remove")
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="parametername"> @lang("$string_file.name")
                                                        :<span class="text-danger">*</span></label>
                                                    {!! Form::text("parametername[".$i."]",old('parametername',$value->parameterName),['class'=>'form-control','id'=>'parametername'.$i,'placeholder'=>'']) !!}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="begintime">@lang("$string_file.start_time")
                                                        :<span class="text-danger">*</span></label>
                                                    <input type="text" value="{!! $value->slot_start_time !!}"
                                                           class="form-control timepicker" data-plugin="clockpicker"
                                                           data-autoclose="true" id="begintime{!! $i !!}" q="{!! $i !!}"
                                                           name="begintime[{!! $i !!}]"
                                                           onchange="EnableDisableDaySelector(this);" placeholder=""
                                                           autocomplete="off">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="endtime">@lang("$string_file.end_time"):<span
                                                                class="text-danger">*</span></label>
                                                    <input type="text" value="{!! $value->slot_end_time !!}"
                                                           class="form-control timepicker" data-plugin="clockpicker"
                                                           data-autoclose="true" id="endtime{!! $i !!}" q="{!! $i !!}"
                                                           name="endtime[{!! $i !!}]"
                                                           onchange="EnableDisableDaySelector(this);" placeholder=""
                                                           autocomplete="off">

                                                    <label class="radio-inline"
                                                           style="margin-right: 2%;margin-left: 5%;margin-top: 3%;">
                                                        <input type="radio" value="1"
                                                               @if($value->slot_end_day == 1) checked
                                                               @endif q={{$i}} onclick="calculate(this);"
                                                               name="optradio[{!! $i !!}]">@lang("$string_file.next_day")
                                                    </label>
                                                    <label class="radio-inline">
                                                        <input type="radio" value="2"
                                                               @if($value->slot_end_day == 2) checked
                                                               @endif q={{$i}} onclick="calculate(this);"
                                                               name="optradio[{!! $i !!}]">@lang("$string_file.same_day")
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="slot_charges">
                                                        @lang("$string_file.slot_charges"):<span
                                                                class="text-danger">*</span></label>
                                                    <input type="number" value="{{$value->slot_charges}}"
                                                           class="form-control" min="0" step="0.01"
                                                           id="slot_charges{!! $i !!}" name="slot_charges[{!! $i !!}]"
                                                           placeholder="" autocomplete="off">
                                                    <label class="radio-inline"
                                                           style="margin-right: 2%;margin-left: 5%;margin-top: 3%;">
                                                        <input type="radio" value="1"
                                                               @if($value->slot_charge_type == 1) checked
                                                               @endif name="charge_type[{!! $i !!}]">@lang("$string_file.nominal")
                                                    </label>
                                                    <label class="radio-inline">
                                                        <input type="radio" value="2"
                                                               @if($value->slot_charge_type == 2) checked
                                                               @endif name="charge_type[{!! $i !!}]">@lang("$string_file.multiplier")
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @php $i++; @endphp
                                @endforeach
                                <input type="hidden" value="{{ count($data['price_card']->ExtraCharges)}}" id="checkBoxCount">
                            @else
                                <div class="row" style="text-align: center;">
                                    <div class="col-md-9">
                                        <label for="weekdays">@lang("$string_file.select_week_days")
                                            :<span class="text-danger">*</span></label>
                                        <div class="form-group">
                                            <div class="weekDays-selector" index="0">
                                                @foreach($days as $key=>$day)
                                                    @php $day_letter = substr($day, 0, 1); @endphp
                                                    <input type="checkbox" value="{!! $key !!}"
                                                           name="week_days[0][{!! $key !!}]"
                                                           id="fg-weekday-0-{{$key}}" class="weekday weekday0"/>
                                                    <label for="fg-weekday-0-{{$key}}">{!! $day_letter !!}</label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group-btn">
                                            <button class="btn btn-success add-more" type="button">
                                                <i class="glyphicon glyphicon-plus"></i> @lang("$string_file.add")  @lang("$string_file.new")  @lang("$string_file.slots")
                                            </button>
                                            <button class="btn btn-primary float-right" id="reset_week" type="button">
                                                @lang("$string_file.reset")
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="parametername"> @lang("$string_file.name") :<span
                                                        class="text-danger">*</span></label>
                                            {!! Form::text('parametername[0]',old('parametername.0'),['class'=>'form-control','id'=>'parametername0','placeholder'=>'']) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="begintime">@lang("$string_file.start_time") :<span
                                                        class="text-danger">*</span></label>
                                            <input type="text" class="form-control timepicker" data-plugin="clockpicker"
                                                   data-autoclose="true" id="begintime0" q="0" name="begintime[0]"
                                                   onchange="EnableDisableDaySelector(this);" placeholder=""
                                                   autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="endtime">@lang("$string_file.end_time") :<span
                                                        class="text-danger">*</span></label>
                                            <input type="text" class="form-control timepicker" data-plugin="clockpicker"
                                                   data-autoclose="true" id="endtime0" q="0" name="endtime[0]"
                                                   onchange="EnableDisableDaySelector(this);" placeholder=""
                                                   autocomplete="off">
                                            <label class="radio-inline"
                                                   style="margin-right: 2%;margin-left: 5%;margin-top: 3%;">
                                                <input type="radio" value="1" q="0" onclick="calculate(this);"
                                                       name="optradio[0]">@lang("$string_file.next_day")
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" value="2" q="0" onclick="calculate(this);"
                                                       name="optradio[0]">@lang("$string_file.same_day")
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="slot_charges">
                                                @lang("$string_file.slot_charges") :<span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" min="0" step="0.01"
                                                   id="slot_charges0" name="slot_charges[0]" placeholder=""
                                                   autocomplete="off">
                                            <label class="radio-inline"
                                                   style="margin-right: 2%;margin-left: 5%;margin-top: 3%;">
                                                <input type="radio" value="1" checked
                                                       name="charge_type[0]">@lang("$string_file.nominal")
                                            </label>
                                            <label class="radio-inline">
                                                <input type="radio" value="2"
                                                       name="charge_type[0]">@lang("$string_file.multiplier")
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="checkBoxCount" id="checkBoxCount" value="0">
                            @endif
                        </div>
                        <hr>
                        {{-- ============= END NIGHT / TIME CHARGES SECTION ============= --}}
                    @endif
                        @if(empty($is_pos_service))
                            <div id="slab-section">
                                <h5 class=""><i class="icon fa-map-marker" aria-hidden="true"></i>
                                    @lang("$string_file.distance_slab_details")
                                    (<span class="text-danger">@lang("$string_file.admin_slab_fill")</span>)

                                    <button class="btn btn-info btn-sm float-right" type="button" id="add_more_slot">
                                        <i class="fa fa-plus">@lang("$string_file.add_more")</i>
                                    </button>
                                </h5>

                                @php  $arr_detail = !empty($data['price_card']->PriceCardDetail) ? $data['price_card']->PriceCardDetail->toArray() : [];
                                @endphp
                                <div id="time_slot_original">
                                @for($i = 0; $i<$slab_count; $i++)
                                    @php
                                        $required = $i == 0 ? true:false ;
                                        $mandatory_astrick = $i == 0 ? "*":"";
                                        $detail = isset($arr_detail[$i]) ? $arr_detail[$i] : NULL;
                                    @endphp
                                    {!! Form::hidden('price_card_detail_id[]',isset($detail['id']) ? $detail['id'] : NULL) !!}
                                    <div class="row" id="row_id_{{$i}}">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="name">@lang("$string_file.distance")  @lang("$string_file.from")
                                                    <span class="text-danger">{{$mandatory_astrick}}</span>
                                                </label>
                                                {!! Form::number('distance_from[]',old('distance_from[]',isset($detail['distance_from']) ? $detail['distance_from'] : ''),['class'=>'form-control','id'=>'distance_from','placeholder'=>'','required'=>$required,'min'=>0, 'step'=>'0.5']) !!}
                                                @if ($errors->has('distance_from'))
                                                    <span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="name">@lang("$string_file.distance")  @lang("$string_file.to") <span
                                                            class="text-danger">{{$mandatory_astrick}}</span>
                                                </label>
                                                {!! Form::number('distance_to[]',old('distance_to[]',isset($detail['distance_to']) ? $detail['distance_to'] : ''),['class'=>'form-control','id'=>'distance_to','placeholder'=>'','required'=>$required,'min'=>0, 'step'=>'0.5']) !!}
                                                @if ($errors->has('distance_to'))
                                                    <span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($price_card_for == 2)
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="name">@lang("$string_file.cart_amount")
                                                        <span class="text-danger">{{$mandatory_astrick}}</span>
                                                    </label>
                                                    {!! Form::number('cart_amount[]',old('cart_amount[]',isset
                                                    ($detail['cart_amount']) ? $detail['cart_amount'] :NULL),
                                                    ['class'=>'form-control','id'=>'cart_amount','placeholder'=>'',
                                                    'required'=>$required,'min'=>0,'step'=>$data['step_value']]) !!}
                                                    @if ($errors->has('cart_amount'))
                                                        <span class="help-block"><strong>{{ $errors->first('cart_amount') }}</strong></span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="name">@lang("$string_file.condition") <span
                                                                class="text-danger">{{$mandatory_astrick}}</span>
                                                    </label>
                                                    {!! Form::select('condition[]',$data['condition'],old('condition[]',isset($detail['condition']) ? $detail['condition'] :NULL),['class'=>'form-control','required'=>$required,'id'=>'condition']) !!}
                                                    @if ($errors->has('condition'))
                                                        <span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="name">@lang("$string_file.slab_amount") <span
                                                            class="text-danger">{{$mandatory_astrick}}</span>
                                                </label>
                                                {!! Form::number('slab_amount[]',old('slab_amount[]',isset($detail['slab_amount']) ? $detail['slab_amount'] : ''),['class'=>'form-control','id'=>'slab_amount','required'=>$required,'min'=>0,'maxlength'=>10,'step'=>$data['step_value']]) !!}
                                                @if ($errors->has('slab_amount'))
                                                    <span class="help-block"><strong>{{ $errors->first('slab_amount') }}</strong></span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="name">@lang("$string_file.status")
                                                    <span class="text-danger">{{$mandatory_astrick}}</span>
                                                </label>
                                                {!! Form::select('detail_status[]',['1' =>trans("$string_file.active"),'2' =>trans("$string_file.inactive")],old('detail_status[]',isset($detail['status']) ? $detail['status'] :1),['class'=>'form-control','required'=>$required,'id'=>'detail_status']) !!}
                                                @if ($errors->has('detail_status'))
                                                    <span class="help-block"><strong>{{ $errors->first('detail_status') }}</strong></span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                                </div>
                            </div>
                        @endif
                        <input type="hidden" id="total_slab" value="{{$slab_count}}">
                        @if(isset($configuration->user_time_charges) && $configuration->user_time_charges == 1 && $price_card_for == 2)
                            @php $time_charges = isset($data['price_card']['time_charges_details']) ? json_decode($data['price_card']['time_charges_details']) : [] @endphp
                            {{-- except grocery--}}
                            <div id="time_charges_div"
                                 @if($id == NULL || (!empty($data['price_card']['segment_id'] && $data['price_card']['segment_id'] !=4))) class="custom-hidden" @endif>
                                <h5 class=""><i class="icon fa-clock-o" aria-hidden="true"></i>
                                    @lang("$string_file.time_charges")
                                </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">@lang("$string_file.time_from")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            {!! Form::text('time_from',old('time_from',isset($time_charges->time_from) ? $time_charges->time_from : NULL),['class'=>'form-control timepicker', "data-plugin"=>"clockpicker", "data-autoclose"=>"true",'id'=>'time_from']) !!}
                                            @if ($errors->has('time_from'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('time_from') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">@lang("$string_file.time_to")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            {!! Form::text('time_to',old('time_to',isset($time_charges->time_to) ? $time_charges->time_to : NULL),['class'=>'form-control timepicker', "data-plugin"=>"clockpicker", "data-autoclose"=>"true", 'id'=>'time_to']) !!}
                                            @if ($errors->has('time_to'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('time_to') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">@lang("$string_file.charges_type")
                                                <span
                                                        class="text-danger">*</span>
                                            </label>
                                            {!! Form::select('charges_type',array('' => 'select','1' => trans("$string_file.flat"),'2' => trans("$string_file.percentage")),old('charges_type',isset($time_charges->charges_type) ? $time_charges->charges_type : NULL),['class'=>'form-control','id'=>'charges_type']) !!}
                                            @if ($errors->has('charges_type'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('charges_type') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">@lang("$string_file.charges")<span
                                                        class="text-danger">*</span>
                                            </label>
                                            {!! Form::number('charges',old('charges',isset($time_charges->charges) ? $time_charges->charges : NULL),['class'=>'form-control','id'=>'charges','min'=>0,'step'=>$data['step_value']]) !!}
                                            @if ($errors->has('charges'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('charges') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">@lang("$string_file.charges_parameter")
                                                <span class="text-danger">*</span>
                                            </label>
                                            {!! Form::text('charge_parameter',old('charge_parameter',isset($time_charges->charge_parameter) ? $time_charges->charge_parameter : NULL),['class'=>'form-control','id'=>'charge_parameter']) !!}
                                            @if ($errors->has('charge_parameter'))
                                                <span class="help-block">
                                                    <strong>{{ $errors->first('charge_parameter') }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                        @endif
                        <div class="form-actions float-right">
                            @if($id == NULL || $edit_permission)
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check-square-o"></i>{!! $data['submit_button'] !!}
                                </button>
                            @else
                                <span style="color: red"
                                      class="float-right">@lang("$string_file.demo_warning_message")</span>
                            @endif
                        </div>
                        {!! Form::close() !!}
                    </section>
                </div>
            </div>
        </div>
    </div>
    @include('merchant.shared.info-setting',['info_setting'=>$info_setting,'page_name'=>'add_text'])
@endsection
@section('js')
    <script type="text/javascript">
        function getSegment() {
            $("#area_segment").empty();
            $("#area_segment").append('<option value="">@lang("$string_file.select")</option>');
            $("#service_type_id").empty();
            $("#service_type_id").append('<option value="">@lang("$string_file.select")</option>');
            var area_id = $("#country_area_id option:selected").val();
            if (area_id != "") {
                $("#loader1").show();
                var token = $('[name="_token"]').val();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: '<?php echo route('get.area.segment') ?>',
                    data: {area_id: area_id, segment_group_id: 1, sub_group_for_admin: 2},
                    success: function (data) {
                        $("#area_segment").empty();
                        $('#area_segment').html(data);
                    }
                });
                $("#loader1").hide();
            }
        }

        function getService() {
            var area_id = $("#country_area_id option:selected").val();
            var segment_id = $("#area_segment option:selected").val();
            var price_card_for = {{$price_card_for}};
            if(price_card_for == 2){
                var calling_from = "USER";
            }else{
                var calling_from = "DRIVER";
            }
            // here segment group is sending as 2 because there is no vehicle type in case of food grocery price card
            if (area_id != "") {
                $("#loader1").show();
                var token = $('[name="_token"]').val();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    method: 'POST',
                    url: '<?php echo route('merchant.area.services') ?>',
                    data: {area_id: area_id, segment_id: segment_id, segment_group: 2, calling_from: calling_from},
                    success: function (data) {
                        $('#service_type_id').html(data);
                    }
                });
                $("#loader1").hide();
            }
        }

        // on service type change — POS check
        $(document).on("change", "#service_type_id", function() {
            var service_type_id = $(this).val();
            if (service_type_id == "") return;

            var token = $('[name="_token"]').val();
            $.ajax({
                headers: { 'X-CSRF-TOKEN': token },
                method: 'POST',
                url: "{{ route('food-grocery.serviceType.isPOS') }}",
                data: { service_type_id: service_type_id },
                success: function (data) {
                    if (data.is_pos == 1) {
                        $('#slab-section').hide();
                        // remove required from slab fields
                        $('input[name="distance_from[]"]').prop('required', false);
                        $('input[name="distance_to[]"]').prop('required', false);
                        $('input[name="slab_amount[]"]').prop('required', false);
                    } else {
                        $('#slab-section').show();
                        // restore required on first slab row
                        $('input[name="distance_from[]"]:first').prop('required', true);
                        $('input[name="distance_to[]"]:first').prop('required', true);
                        $('input[name="slab_amount[]"]:first').prop('required', true);
                    }
                }
            });
        });

        $(document).ready(function (e) {
            $(document).on("change", "#area_segment", function (e) {
                var segment_id = $("#area_segment option:selected").val();
                $("#time_charges_div").hide();
                if (segment_id == 4) {
                    $("#time_charges_div").show();
                }
            });
        });

        // add more slots (distance slabs)
        $(document).ready(function (e) {
            $(document).on("click", "#add_more_slot", function (e) {
                var total_slab = $("#total_slab").val();
                var row_id = total_slab;
                var next_row = parseInt(row_id) + 1;
                $("#total_slab").val(next_row);
                var new_row = '<div class="row" id="row_id_' + row_id + '"><div class="col-md-2"><div class="form-group">' +
                    '<label for="name">@lang("$string_file.distance")  @lang("$string_file.from")' +
                    '<span class="text-danger">{{$mandatory_astrick}}</span>' +
                    '</label>' +
                    '{!! Form::number('distance_from[]',old('distance_from[]',''),['class'=>'form-control','id'=>'distance_from','placeholder'=>'','required'=>$required,'min'=>0, 'step'=>"0.5"]) !!}' +
                    '@if ($errors->has('distance_from'))' +
                    '<span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>' +
                    '@endif' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<div class="form-group">' +
                    '<label for="name">@lang("$string_file.distance")@lang("$string_file.to")' +
                    '<span class="text-danger">{{$mandatory_astrick}}</span>' +
                    '</label>' +
                    '{!! Form::number('distance_to[]',old('distance_to[]',''),['class'=>'form-control','id'=>'distance_from','placeholder'=>'','required'=>$required,'min'=>0, 'step'=>"0.5"]) !!}' +
                    '@if ($errors->has('distance_to'))' +
                    '<span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>' +
                    '@endif' +
                    '</div>' +
                    '</div>' +
                    '@if($price_card_for == 2)' +
                    '<div class="col-md-2">' +
                    '<div class="form-group">' +
                    '<label for="name">@lang("$string_file.cart_amount")' +
                    '<span class="text-danger">{{$mandatory_astrick}}</span>' +
                    '</label>' +
                    '{!! Form::number('cart_amount[]',old('cart_amount[]',''),['class'=>'form-control','id'=>'cart_amount','placeholder'=>'','required'=>$required,'step'=>$data['step_value'],'min'=>0]) !!}' +
                    '@if ($errors->has('slab_amount'))' +
                    '<span class="help-block"><strong>{{ $errors->first('slab_amount') }}</strong></span>' +
                    '@endif' +
                    '</div></div>' +

                    '<div class="col-md-2">' +
                    '<div class="form-group">' +
                    '<label for="name">@lang("$string_file.condition")' +
                    '<span class="text-danger">{{$mandatory_astrick}}</span>' +
                    '</label>' +
                    '{!! Form::select('condition[]',$data['condition'],old('condition[]',NULL),['class'=>'form-control','required'=>$required,'id'=>'condition'])
                    !!}' +
                    '@if ($errors->has('condition'))' +
                    '<span class="help-block"><strong>{{ $errors->first('distance_from') }}</strong></span>' +
                    '@endif' +
                    '</div>' +
                    '</div>' +
                    '@endif' +
                    '<div class="col-md-2">' +
                    '<div class="form-group">' +
                    '<label for="name">@lang("$string_file.slab_amount")' +
                    '<span class="text-danger">{{$mandatory_astrick}}</span>' +
                    '</label>' +
                    '{!! Form::number('slab_amount[]',old('slab_amount[]',''),['class'=>'form-control','id'=>'slab_amount','required'=>$required,'step'=>$data['step_value'],'min'=>0]) !!}' +
                    '@if($errors->has('slab_amount'))' +
                    '<span class="help-block"><strong>{{ $errors->first('slab_amount') }}</strong></span>' +
                    '@endif' +
                    '</div>' +
                    '</div>' +
                    '<div class="col-md-2">' +
                    '<div class="form-group">' +
                    '<label for="name"></label>' +
                    '<button type="button" class="btn btn-danger btn-sm mt-35 remove_button" id="' + row_id + '">' +
                    '@lang("$string_file.delete")' +
                    '</button>'+
                '</div>' +
                '</div>' +
                '</div>';

                $("#time_slot_original").append(new_row);

            });
        });

        $(document).ready(function (e)
        {
            $(document).on("click", ".remove_button", function (e)
            {
              var row_id = $(this).attr('id');
              $("#row_id_"+row_id).remove();
            });
        });

        /* ================================================================ */
        /* ============ NIGHT / TIME CHARGES (SLOT BASED) JS : NEW ======== */
        /* ================================================================ */
        function calculate(data) {
            var radio_q_value = $(data).attr('q');
            var radio_type = $(data).val();
            var current_start_time = $("input[name='begintime[" + radio_q_value + "]']").val();
            var current_end_time = $("input[name='endtime[" + radio_q_value + "]']").val();
            if (radio_type == 2) {
                if (current_end_time < current_start_time) {
                    alert("{{trans($string_file.'.wrong_night_peak_same_day')}}");
                    $("input[name='optradio[" + radio_q_value + "]']").prop('checked', false);
                }
            }
        }

        function EnableDisableDaySelector(data) {
            var q_value = $(data).attr('q');
            var start_time = $("input[name='begintime[" + q_value + "]']").val();
            var end_time = $("input[name='endtime[" + q_value + "]']").val();
            if ((start_time != '') && (end_time != '')) {
                $("input[name='optradio[" + q_value + "]']").attr('disabled', false);
            } else {
                $("input[name='optradio[" + q_value + "]']").prop('checked', false);
                $("input[name='optradio[" + q_value + "]']").attr('disabled', true);
            }
        }

        function validateNumber(event) {
            var key = window.event ? event.keyCode : event.which;
            if (event.keyCode === 8 || event.keyCode === 46) {
                return true;
            } else if (key < 48 || key > 57) {
                return false;
            } else {
                return true;
            }
        }

        $(document).ready(function () {
            $('[id^=slot_charges]').keypress(validateNumber);
            var max_fields = 5;
            var count = parseInt($("#checkBoxCount").val()) || 0;
            var arr_days = @json($days);

            $(".add-more").click(function () {
                if (count < max_fields) {
                    count++;
                    document.getElementById("checkBoxCount").value = count;

                    var dayHtml = '';
                    $.each(arr_days, function (key, day) {
                        dayHtml += '<input type="checkbox" name="week_days[' + count + '][' + key + ']" value="' + key + '" id="fg-weekday-' + count + '-' + key + '" class="weekday mr-1 ml-1">' +
                            '<label for="fg-weekday-' + count + '-' + key + '">' + day.substr(0, 1) + '</label>';
                    });

                    var html = '<div class="dynamic-copy">' +
                        '<div class="row" style="text-align: center;">' +
                        '<div class="col-md-9">' +
                        '<label for="weekdays">' +
                        '@lang("$string_file.select_week_days"):' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<div class="form-group">' +
                        '<div class="weekDays-selector" index="' + count + '">' +
                        dayHtml +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="col-md-3">' +
                        '<div class="input-group-btn">' +
                        '<button class="btn btn-danger remove" type="button"><i class="glyphicon glyphicon-remove"></i>' +
                        '@lang("$string_file.remove")' +
                        '</button>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<div class="row">' +
                        '<div class="col-md-3">' +
                        '<div class="form-group">' +
                        '<label for="parametername"> @lang("$string_file.name") : ' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type = "text" class = "form-control" id = "parametername' + count + '" name = "parametername[' + count + ']" placeholder = "" autocomplete = "off">' +
                        '</div>' +
                        '</div>' +
                        '<div class="col-md-3">' +
                        '<div class="form-group">' +
                        '<label for="begintime">' +
                        '@lang("$string_file.start_time"):' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type="text" data-autoclose="true" data-plugin="clockpicker" class="form-control timepicker' + count + '" q="' + count + '" id="begintime' + count + '" name="begintime[' + count + ']" onchange="EnableDisableDaySelector(this);" placeholder="" autocomplete="off">' +
                        '</div>' +
                        '</div>' +
                        '<div class="col-md-3">' +
                        '<div class="form-group">' +
                        '<label for="endtime">' +
                        '@lang("$string_file.end_time") :' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type="text" data-autoclose="true" data-plugin="clockpicker" class="form-control timepicker' + count + '" q="' + count + '" id="endtime' + count + '" name="endtime[' + count + ']" onchange="EnableDisableDaySelector(this);" placeholder="" autocomplete="off">' +
                        '<label class="radio-inline" style="margin-right: 2%;margin-left: 5%;margin-top: 3%;">' +
                        '<input type="radio" value="1" q="' + count + '" name="optradio[' + count + ']" onclick="calculate(this);">@lang("$string_file.next_day")' +
                        '</label>' +
                        '<label class="radio-inline"><input type="radio" value="2" q="' + count + '" name="optradio[' + count + ']" onclick="calculate(this);">@lang("$string_file.same_day")' +
                        '</label>' +
                        '</div>' +
                        '</div>' +
                        '<div class="col-md-3">' +
                        '<div class="form-group">' +
                        '<label for="slot_charges">' +
                        '@lang("$string_file.slot_charges") :' +
                        '<span class="text-danger">*</span>' +
                        '</label>' +
                        '<input type="text" class="form-control" id="slot_charges' + count + '" name="slot_charges[' + count + ']" placeholder="" autocomplete="off">' +
                        '<label class="radio-inline" style="margin-right: 2%;margin-left: 5%;margin-top: 3%;">' +
                        '<input type="radio" value="1" checked name="charge_type[' + count + ']">@lang("$string_file.nominal")' +
                        '</label>' +
                        '<label class="radio-inline">' +
                        '<input type="radio" value="2" name="charge_type[' + count + ']">@lang("$string_file.multiplier") </label>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '<hr>' +
                        '</div>';
                    $("#after-add-more").append(html);
                    $('.timepicker' + count).clockpicker({});
                    $('[id^=slot_charges]').keypress(validateNumber);
                }
            });

            $("body").on("click", ".remove", function () {
                count--;
                document.getElementById("checkBoxCount").value = count;
                $(this).parents(".dynamic-copy").remove();
            });

            $("#reset_week").click(function () {
                $(".weekday0").prop("checked", false);
                $("#parametername0").prop("required", false);
                $("#slot_charges0").prop("required", false);
                $('#parametername0').val('');
                $('#slot_charges0').val('');
                $("#begintime0").prop("required", false);
                $("#endtime0").prop("required", false);
                $('#begintime0').val('');
                $('#endtime0').val('');
                $('input[name="optradio[0]"]').prop('checked', false);
            });
        });

        // Submit validation for night charge slots (optional rows allowed,
        // but partially-filled rows are blocked)
        $('#food-grocery-form').on('submit', function (e) {
            var week_validate_error = 0;
            var parameter_validate_error = 0;
            $('.weekDays-selector').each(function () {
                var index = $(this).attr('index');
                var has_days = $(this).children('input[type=checkbox]:checked').length > 0;
                var has_name = $('input[name="parametername[' + index + ']"]').val() != '';
                var has_begin = $('input[name="begintime[' + index + ']"]').val() != '';
                var has_end = $('input[name="endtime[' + index + ']"]').val() != '';
                var has_charge = $('input[name="slot_charges[' + index + ']"]').val() != '';
                var any_filled = has_name || has_begin || has_end || has_charge;
                var all_filled = has_name && has_begin && has_end && has_charge;

                if (any_filled && !has_days) {
                    week_validate_error = 1;
                }
                if (has_days && !all_filled) {
                    parameter_validate_error = 1;
                }
            });

            if (week_validate_error != 0) {
                e.preventDefault();
                alert("@lang("$string_file.select_week_days")");
            } else if (parameter_validate_error != 0) {
                e.preventDefault();
                alert("@lang("$string_file.enter_week_parameter")");
            }
        });
        /* ============= END NIGHT / TIME CHARGES JS ============= */

    </script>
@endsection
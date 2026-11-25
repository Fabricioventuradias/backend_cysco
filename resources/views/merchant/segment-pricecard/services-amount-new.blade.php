<h5>@lang("$string_file.set_service_charges_as_fixed")</h5>
<hr>
<div class="row">
    @foreach($arr_services as $service)
        @php
            $amount = isset($service->SegmentPriceCardDetail) ? $service->SegmentPriceCardDetail->amount : NULL;
            $segment_additional_charge =isset($service->SegmentPriceCardDetail) ? $service->SegmentPriceCardDetail->segment_additional_charge : NULL;
            $detail_id = isset($service->SegmentPriceCardDetail) ? $service->SegmentPriceCardDetail->id : NULL;
        @endphp
    <div class="col-md-4">
        <div class="form-group">
            <label for="firstName3">
               {{!empty($service->serviceName($merchant_id)) ? $service->serviceName($merchant_id) : $service->serviceName}}
                <span class="text-danger">*</span>
            </label>
            {!! Form::hidden('detail_id['.$service->id.']',old('detail_id',$detail_id),['class'=>'form-control','id'=>'detail_id','placeholder'=>"",'required'=>true]) !!}
            
            @if($service->price_type == 1 || empty($service->price_type))

                {!! Form::number(
                    'fixed_amount['.$service->id.']',
                    old('fixed_amount', $amount),
                    [
                        'class' => 'form-control',
                        'id' => 'sequence_number',
                        'placeholder' => "",
                        'required' => true,
                        'min' => 0,
                        'step' => 0.01
                    ]
                ) !!}
            
            @else
            
                <div class="row">
            
                    <!-- First input -->
                    <div class="col-md-6">
                        {!! Form::number(
                            'fixed_amount['.$service->id.']',
                            old('fixed_amount', $amount),
                            [
                                'class' => 'form-control',
                                'placeholder' => "Amount",
                                'required' => true,
                                'min' => 0,
                                'step' => 0.01
                            ]
                        ) !!}
                    </div>
            
                    <!-- Second input -->
                    <div class="col-md-6">
                        {!! Form::number(
                            'segment_additional_charge['.$service->id.']',
                            old('segment_additional_charge', $segment_additional_charge),
                            [
                                'class' => 'form-control',
                                'placeholder' => "Additional Amount",
                                'required' => true,
                                'min' => 0,
                                'step' => 0.01
                            ]
                        ) !!}
                    </div>
            
                </div>
            
            @endif
                {!! Form::select(
                    'price_type',
                    [
                        '' => 'Select Pricing Model',
                        '1' => 'Fixed',
                        '2' => 'Bidding',
                        '3' => 'Fixed + Hourly',
                        '4' => 'Fixed + Additional Charge',
                        '5' => 'Fixed + Site Visit',
                    ],
                    old('price_type', $service->price_type ?? ''),
                    [
                        'class' => 'form-control mt-2',
                        'id' => 'price_typee',
                        'disabled' => true
                    ]
                ) !!}
            @if ($errors->has('fixed_amount'))
                <label class="text-danger">{{ $errors->first('fixed_amount') }}</label>
            @endif
        </div>
    </div>
@endforeach
</div>

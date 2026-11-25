@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">
                    <a href="{{ route('merchant.handyman-store.subscription.index') }}"
                       class="btn btn-default" style="margin:10px;">
                        <i class="fa fa-arrow-left"></i> @lang("$string_file.back")
                    </a>
                </div>
                <h3 class="panel-title">
                    <i class="fa fa-crown"></i>
                    {{ empty($subscription) ? 'Add Subscription Plan' : 'Edit Subscription Plan' }}
                </h3>
            </header>
            <div class="panel-body container-fluid">
                {!! Form::open([
                    'url'    => route('merchant.handyman-store.subscription.save', $subscription->id ?? null),
                    'method' => 'POST',
                    'files'  => true,
                ]) !!}
                <div class="row">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Subscription Name <span class="text-danger">*</span></label>
                            {!! Form::text('subscription_name', $subscription->subscription_name ?? null, [
                                'class' => 'form-control', 'placeholder' => 'e.g. Basic Plan',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Amount <span class="text-danger">*</span></label>
                            {!! Form::number('amount', $subscription->amount ?? 0, [
                                'class' => 'form-control', 'step' => '0.01', 'min' => '0',
                            ]) !!}
                            <small class="text-muted">Set 0 for free plan</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>No. of Service Posts <span class="text-danger">*</span></label>
                            {!! Form::number('no_of_service_post', $subscription->no_of_service_post ?? 10, [
                                'class' => 'form-control', 'min' => '0',
                            ]) !!}
                            <small class="text-muted">Set 0 for unlimited</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Validity Days <span class="text-danger">*</span></label>
                            {!! Form::number('validity_days', $subscription->validity_days ?? 0, [
                                'class' => 'form-control', 'min' => '0',
                            ]) !!}
                            <small class="text-muted">Set 0 for lifetime</small>
                        </div>
                    </div>
                    

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            {!! Form::select('status',
                                [1 => 'Active', 0 => 'Inactive'],
                                $subscription->status ?? 1,
                                ['class' => 'form-control']
                            ) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Is Default?</label>
                            {!! Form::select('is_default',
                                [0 => 'No', 1 => 'Yes'],
                                $subscription->is_default ?? 0,
                                ['class' => 'form-control']
                            ) !!}
                            <small class="text-muted">
                                Auto-assigned when new store created.
                            </small>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Sequence</label>
                            {!! Form::number('sequence', $subscription->sequence ?? 0, [
                                'class' => 'form-control', 'min' => '0',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Logo</label>
                            {!! Form::file('logo', ['class' => 'form-control', 'accept' => 'image/*']) !!}
                            @if(!empty($subscription->logo))
                                <div class="mt-10">
                                    <img src="{{ get_image($subscription->logo, 'icon',$subscription->merchant_id,true, false) }}"
                                         style="width:50px;height:50px;object-fit:cover;border-radius:8px;border:1px solid #ddd;">
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Features --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Features</label>
                            <div id="features-wrapper">
                                @if(!empty($subscription->features))
                                    @foreach($subscription->features as $feature)
                                        <div class="feature-row d-flex gap-10 mb-10">
                                            <input type="text" name="features[]" value="{{ $feature }}"
                                                   class="form-control" placeholder="e.g. Priority Support">
                                            <button type="button" class="btn btn-danger remove-feature">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="feature-row d-flex gap-10 mb-10">
                                        <input type="text" name="features[]" class="form-control"
                                               placeholder="e.g. Priority Support">
                                        <button type="button" class="btn btn-danger remove-feature" style="display:none;">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-success btn-sm mt-5" id="add-feature">
                                <i class="fa fa-plus"></i> Add Feature
                            </button>
                        </div>
                    </div>

                </div>

                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> @lang("$string_file.save")
                </button>

                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $('#add-feature').on('click', function() {
        var row = '<div class="feature-row d-flex gap-10 mb-10">' +
                  '<input type="text" name="features[]" class="form-control" placeholder="e.g. Priority Support">' +
                  '<button type="button" class="btn btn-danger remove-feature"><i class="fa fa-times"></i></button>' +
                  '</div>';
        $('#features-wrapper').append(row);
        toggleRemove();
    });

    $(document).on('click', '.remove-feature', function() {
        $(this).closest('.feature-row').remove();
        toggleRemove();
    });

    function toggleRemove() {
        var rows = $('.feature-row');
        rows.find('.remove-feature').show();
        if (rows.length == 1) rows.first().find('.remove-feature').hide();
    }

    toggleRemove();
</script>
@endsection
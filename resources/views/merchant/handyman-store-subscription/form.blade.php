@extends('merchant.layouts.main')
@section('content')
@php

$includedBenefitsValues = old('included_benefits', isset($plan->included_benefits) ? json_decode($plan->included_benefits, true) : []);
@endphp
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')

            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('merchant.subscription.handyman.store.index') }}">
                                <button type="button" class="btn btn-icon btn-success"><i class="wb-reply"></i></button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class="icon wb-plus" aria-hidden="true"></i> Subscription Plan
                    </h3>
                </header>

                <div class="panel-body container-fluid">
                    <form id="subscription-plan-form"
                          action="{{ route('merchant.subscription.handyman.store.save', $id) }}"
                          method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $id }}">

                        <fieldset>
                            <div class="row">

                                <!-- Name -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Plan Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                               value="{{ old('name', $plan->name ?? '') }}" required>
                                        @error('name') <label class="text-danger">{{ $message }}</label> @enderror
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Plan Price <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control" name="price"
                                               value="{{ old('price', $plan->price ?? '') }}" required>
                                        @error('price') <label class="text-danger">{{ $message }}</label> @enderror
                                    </div>
                                </div>

                                <!-- Duration -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Duration (days) <span class="text-danger">*</span></label>
                                        <input type="number" min="1" class="form-control" name="duration_days"
                                               value="{{ old('duration_days', $plan->duration_days ?? 30) }}" required>
                                        @error('duration_days') <label class="text-danger">{{ $message }}</label> @enderror
                                    </div>
                                </div>

                                <!-- Discount type (price type: fixed / percentage) -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Discount Type <span class="text-danger">*</span></label>
                                        <select name="discount_type" id="discount_type" class="form-control"
                                                onchange="toggleMaxDiscount()" required>
                                            <option value="1" {{ old('discount_type', $plan->discount_type ?? 1) == 1 ? 'selected' : '' }}>Fixed Amount</option>
                                            <option value="2" {{ old('discount_type', $plan->discount_type ?? '') == 2 ? 'selected' : '' }}>Percentage (%)</option>
                                        </select>
                                        @error('discount_type') <label class="text-danger">{{ $message }}</label> @enderror
                                    </div>
                                </div>

                                <!-- Discount value -->
                                <div class="col-md-4" id="discount_amount_wrapper">
                                    <div class="form-group">
                                        <label>Discount Value <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control" name="discount_amount"
                                               value="{{ old('discount_amount', $plan->discount_amount ?? '') }}" >
                                    </div>
                                </div>

                                <!-- Max discount cap (percentage only) -->
                                <div class="col-md-4" id="max_discount_wrapper">
                                    <div class="form-group">
                                        <label>Discount percentage</label>
                                        <input type="number" step="0.01" min="0" class="form-control" name="discount_percentage"
                                               value="{{ old('discount_percentage', $plan->discount_percentage ?? '') }}">
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea class="form-control" name="description" rows="2">{{ old('description', $plan->description ?? '') }}</textarea>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="1" {{ old('status', $plan->status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="2" {{ old('status', $plan->status ?? '') == 2 ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            <!-- INCLUDED BENEFITS -->
                            <div class="col-md-6">
                                <label>
                                    @lang("$string_file.included_benefits")<span class="text-danger">*</span>
                                </label>
                            
                                <div id="included-benefits-wrapper">
                                    @if(!empty($includedBenefitsValues))
                                        @foreach($includedBenefitsValues as $index => $value)
                                            <div class="row included-benefit-row mb-2 align-items-end">
                                                <div class="col-md-8">
                                                    <input type="text" name="included_benefits[]" class="form-control"
                                                           value="{{ $value }}" placeholder="Enter Included Benefit">
                                                </div>
                                                <div class="col-md-2 d-flex">
                                                    <button type="button" class="btn btn-success w-100 add-benefit-row">+</button>
                                                </div>
                                                <div class="col-md-2 d-flex">
                                                    <button type="button" class="btn btn-danger w-100 remove-benefit-row"
                                                        {{ $index == 0 ? 'style=display:none;' : '' }}>-</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="row included-benefit-row mb-2 align-items-end">
                                            <div class="col-md-8">
                                                <input type="text" name="included_benefits[]" class="form-control"
                                                       placeholder="Enter Included Benefit">
                                            </div>
                                            <div class="col-md-2 d-flex">
                                                <button type="button" class="btn btn-success w-100 add-benefit-row">+</button>
                                            </div>
                                            <div class="col-md-2 d-flex">
                                                <button type="button" class="btn btn-danger w-100 remove-benefit-row" style="display:none;">-</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            </div>
                        </fieldset>

                        <div class="form-actions float-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-square-o"></i> {{ $submit_button }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
    
        function toggleMaxDiscount() {
            var type = document.getElementById('discount_type').value;
            var wrapper = document.getElementById('max_discount_wrapper');
            var discount_wrapper = document.getElementById('discount_amount_wrapper');
            
            var hint = document.getElementById('discount_hint');
            if (type === '2') {                 // percentage
                wrapper.style.display = '';
                discount_wrapper.style.display = 'none';
            } else {                            // fixed
                wrapper.style.display = 'none';
                discount_wrapper.style.display = '';
            }
        }
        document.addEventListener('DOMContentLoaded', toggleMaxDiscount);
        
        // INCLUDED BENEFITS -------------------------
        $(document).on('click', '.add-benefit-row', function () {
            let row = $(this).closest('.included-benefit-row').clone();
            row.find('input').val('');
            row.find('.remove-benefit-row').show();
            $('#included-benefits-wrapper').append(row);
            toggleRemoveButtons('#included-benefits-wrapper', '.included-benefit-row', '.remove-benefit-row');
        });
        $(document).on('click', '.remove-benefit-row', function () {
            $(this).closest('.included-benefit-row').remove();
            toggleRemoveButtons('#included-benefits-wrapper', '.included-benefit-row', '.remove-benefit-row');
        });
        
        
    </script>
@endsection
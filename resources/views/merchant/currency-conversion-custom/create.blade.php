@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')
            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title"><i class="wb-flag" aria-hidden="true"></i>
                        @lang("$string_file.currency_exchange_conversion")
                    </h3>
                </header>
                <div class="panel-body container-fluid">
                    <form method="POST" action="{{ route('merchant.currency_conversion.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="country_id">
                                        @lang("$string_file.country")<span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control select2" name="country_id" id="country_id" required>
                                        <option value="">@lang("$string_file.select")</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}"
                                                data-currency="{{ $country->currency_name }}"
                                                {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                                {{ $country->country_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('country_id'))
                                        <label class="danger">{{ $errors->first('country_id') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_currency_display">
                                        @lang("$string_file.base_currency")
                                    </label>
                                    <input type="text" class="form-control" id="base_currency_display"
                                           value="{{ $base_currency_code }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="local_currency_code">
                                        @lang("$string_file.local_currency")<span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" style="text-transform:uppercase"
                                           id="local_currency_code"
                                           name="local_currency_code"
                                           maxlength="3"
                                           placeholder="SRD"
                                           value="{{ old('local_currency_code') }}"
                                           required>
                                    @if ($errors->has('local_currency_code'))
                                        <label class="danger">{{ $errors->first('local_currency_code') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="exchange_rate">
                                        @lang("$string_file.exchange_rate")<span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" step="0.000001" min="0.000001"
                                           id="exchange_rate"
                                           name="exchange_rate"
                                           placeholder="40.00"
                                           value="{{ old('exchange_rate') }}"
                                           required>
                                    @if ($errors->has('exchange_rate'))
                                        <label class="danger">{{ $errors->first('exchange_rate') }}</label>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">
                                        @lang("$string_file.status")<span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="status" id="status" required>
                                        <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>@lang("$string_file.yes")</option>
                                        <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>@lang("$string_file.no")</option>
                                    </select>
                                    @if ($errors->has('status'))
                                        <label class="danger">{{ $errors->first('status') }}</label>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-actions right">
                            <a href="{{ route('merchant.currency_conversion.index') }}" class="btn btn-default">
                                @lang("$string_file.cancel")
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-circle"></i> @lang("$string_file.save")
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
    document.addEventListener("change", function (event) {
        if (event.target.id === "country_id") {
            var selected = event.target.options[event.target.selectedIndex];
            var currency = selected.getAttribute("data-currency");
            var localInput = document.getElementById("local_currency_code");
            if (currency && !localInput.value) {
                localInput.value = currency;
            }
        }
    });
</script>
@endsection
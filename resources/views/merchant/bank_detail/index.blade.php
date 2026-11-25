@extends('merchant.layouts.main')
@section('content')
 <div class="page">
    <div class="page-content">
        <div class="col-lg-12">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@lang("$string_file.bank_details")</h5>

                    @if($detail)
                        @php
                            $badge = [
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'verified' => 'success',
                                'rejected' => 'danger',
                            ][$detail->account_status] ?? 'secondary';
                        @endphp
                        <span class="badge badge-{{ $badge }} text-uppercase">
                            {{ str_replace('_', ' ', $detail->account_status) }}
                        </span>
                    @else
                        <span class="badge badge-secondary text-uppercase">new</span>
                    @endif
                </div>

                @if($fields->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            No bank fields are configured yet. Please add fields in
                            <strong>Bank Field Configuration</strong> first.
                        </div>
                    </div>
                @else
                    <form action="{{ route('merchant.bank.detail.save') }}" method="POST" autocomplete="off">
                        @csrf

                        <div class="card-body">
                            <div class="row">
                                @foreach($fields as $field)
                                    @php
                                        $name    = 'bank_details[' . $field->field_name . ']';
                                        $key     = 'bank_details.' . $field->field_name;
                                        $id      = 'field_' . $field->id;
                                        $current = old($key, $values[$field->field_name] ?? '');
                                        $options = $options_map[$field->id] ?? [];
                                        $invalid = $errors->has($key) ? ' is-invalid' : '';
                                        $type    = $field->field_type;
                                    @endphp

                                    <div class="form-group col-md-6">
                                        <label for="{{ $id }}">
                                            {{ $field->display_text ?: $field->field_name }}
                                            @if($field->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($type == 'textarea')
                                            <textarea name="{{ $name }}"
                                                      id="{{ $id }}"
                                                      rows="3"
                                                      class="form-control{{ $invalid }}"
                                                      @if($field->max_length) maxlength="{{ $field->max_length }}" @endif
                                                      @if($field->is_required) required @endif>{{ $current }}</textarea>

                                        @elseif($type == 'select')
                                            <select name="{{ $name }}"
                                                    id="{{ $id }}"
                                                    class="form-control{{ $invalid }}"
                                                    @if($field->is_required) required @endif>
                                                <option value="">@lang("$string_file.select")</option>
                                                @foreach($options as $value => $label)
                                                    <option value="{{ $value }}" {{ (string) $current === (string) $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>

                                        @elseif($type == 'radio')
                                            <div>
                                                @foreach($options as $value => $label)
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input{{ $invalid }}"
                                                               type="radio"
                                                               name="{{ $name }}"
                                                               id="{{ $id }}_{{ $loop->index }}"
                                                               value="{{ $value }}"
                                                               {{ (string) $current === (string) $value ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="{{ $id }}_{{ $loop->index }}">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>

                                        @else
                                            @php
                                                $input_type = in_array($type, ['number', 'email', 'tel', 'date', 'password'])
                                                    ? $type : 'text';
                                            @endphp
                                            <input type="{{ $input_type }}"
                                                   name="{{ $name }}"
                                                   id="{{ $id }}"
                                                   value="{{ $current }}"
                                                   class="form-control{{ $invalid }}"
                                                   @if($field->max_length && $input_type != 'date') maxlength="{{ $field->max_length }}" @endif
                                                   @if($field->is_required) required @endif>
                                        @endif

                                        @if($field->field_description)
                                            <small class="form-text text-muted">{{ $field->field_description }}</small>
                                        @endif

                                        @error($key)
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                @if($detail)
                                    Last updated: {{ $detail->updated_at }}
                                @else
                                    Not submitted yet
                                @endif
                            </small>
                            <button type="submit" class="btn btn-primary">
                                {{ $detail ? __("$string_file.update") : __("$string_file.submit") }}
                            </button>
                        </div>
                    </form>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
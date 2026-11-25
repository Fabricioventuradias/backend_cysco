@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')

        <div class="panel panel-bordered">
            <header class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa fa-plus-circle"></i>
                    Create Custom Bank Field
                </h3>
            </header>

            <div class="panel-body">
                <form action="{{ route('merchant.bank-fields.store') }}" method="POST" class="form-horizontal">
                    @csrf

                   

                    <!-- Field Name -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">Field Name (Internal) <span class="text-danger">*</span></label>
                        <div class="col-md-6">
                            <input 
                                type="text" 
                                name="field_name" 
                                class="form-control @error('field_name') is-invalid @enderror"
                                placeholder="e.g., account_number (no spaces, use underscores)"
                                value="{{ old('field_name') }}"
                                required
                            >
                            <small class="form-text text-muted">Used internally for data storage (no spaces, use underscores)</small>
                            @error('field_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Display Text -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">Display Text <span class="text-danger">*</span></label>
                        <div class="col-md-6">
                            <input 
                                type="text" 
                                name="display_text" 
                                class="form-control @error('display_text') is-invalid @enderror"
                                placeholder="e.g., Account Number"
                                value="{{ old('display_text') }}"
                                required
                            >
                            <small class="form-text text-muted">Text shown to drivers in the form</small>
                            @error('display_text')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Field Type -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">Field Type <span class="text-danger">*</span></label>
                        <div class="col-md-6">
                            <select name="field_type" id="field_type" class="form-control @error('field_type') is-invalid @enderror" required onchange="handleFieldTypeChange()">
                                <option value="">Select a type</option>
                                <option value="text" {{ old('field_type') === 'text' ? 'selected' : '' }}>Text Input</option>
                                <option value="number" {{ old('field_type') === 'number' ? 'selected' : '' }}>Number Input</option>
                                <option value="email" {{ old('field_type') === 'email' ? 'selected' : '' }}>Email Input</option>
                                <option value="date" {{ old('field_type') === 'date' ? 'selected' : '' }}>Date Picker</option>
                                <option value="select" {{ old('field_type') === 'select' ? 'selected' : '' }}>Dropdown Select</option>
                                <option value="textarea" {{ old('field_type') === 'textarea' ? 'selected' : '' }}>Text Area</option>
                                <option value="checkbox" {{ old('field_type') === 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                            </select>
                            @error('field_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Field Options (for select) -->
                    <div class="form-group" id="options_container" style="display: none;">
                        <label class="col-md-3 control-label">Options (JSON Array)</label>
                        <div class="col-md-6">
                            <textarea 
                                name="field_options" 
                                class="form-control @error('field_options') is-invalid @enderror"
                                placeholder='["Option 1", "Option 2", "Option 3"]'
                                rows="4"
                            >{{ old('field_options') }}</textarea>
                            <small class="form-text text-muted">Format: ["option1", "option2"] for select dropdowns</small>
                            @error('field_options')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Required & Order -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">Field Options</label>
                        <div class="col-md-6">
                            <div class="checkbox checkbox-primary">
                                <input 
                                    type="checkbox" 
                                    name="is_required" 
                                    value="1"
                                    {{ old('is_required') ? 'checked' : '' }}
                                    id="is_required"
                                >
                                <label for="is_required">Required Field</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Display Order</label>
                        <div class="col-md-6">
                            <input 
                                type="number" 
                                name="field_order" 
                                class="form-control"
                                placeholder="0"
                                value="{{ old('field_order', 0) }}"
                            >
                            <small class="form-text text-muted">Fields are displayed in ascending order (0, 1, 2, etc.)</small>
                        </div>
                    </div>

                    <!-- Length Constraints -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">Min Length</label>
                        <div class="col-md-6">
                            <input 
                                type="number" 
                                name="min_length" 
                                class="form-control"
                                value="{{ old('min_length') }}"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label">Max Length</label>
                        <div class="col-md-6">
                            <input 
                                type="number" 
                                name="max_length" 
                                class="form-control"
                                value="{{ old('max_length') }}"
                            >
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">Help Text / Description</label>
                        <div class="col-md-6">
                            <textarea 
                                name="field_description" 
                                class="form-control"
                                placeholder="Helpful text to guide drivers"
                                rows="3"
                            >{{ old('field_description') }}</textarea>
                            <small class="form-text text-muted">Optional: Help text shown below the field</small>
                        </div>
                    </div>

                    <!-- Validation Pattern -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">Validation Pattern (Regex)</label>
                        <div class="col-md-6">
                            <input 
                                type="text" 
                                name="validation_pattern" 
                                class="form-control"
                                placeholder="e.g., ^\d{3}-\d{2}-\d{4}$ for SSN format"
                                value="{{ old('validation_pattern') }}"
                            >
                            <small class="form-text text-muted">Optional: Regular expression for validation</small>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="form-group">
                        <div class="col-md-6 col-md-offset-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Create Field
                            </button>
                            <a href="{{ route('merchant.bank-fields.index') }}" class="btn btn-default">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function handleFieldTypeChange() {
        const fieldType = document.getElementById('field_type').value;
        const optionsContainer = document.getElementById('options_container');
        
        if (fieldType === 'select') {
            optionsContainer.style.display = 'block';
        } else {
            optionsContainer.style.display = 'none';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', handleFieldTypeChange);
</script>
@endsection
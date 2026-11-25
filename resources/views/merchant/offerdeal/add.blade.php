@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <h3 class="panel-title">
                    <i class="wb-flag"></i> Add Offer / Deal
                </h3>
            </header>
            <div class="panel-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('merchant.offerdeal.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
              <input type="text" name="tag" value="{{ old('tag') }}"
                           class="form-control mb-2" placeholder="tag" required>
                    <select name="type" id="offerType" class="form-control mb-3" required>
                        <option value="" disabled {{ old('type') ? '' : 'selected' }}>Select Type</option>
                        <option value="ADVERTISEMENT_SMALL_MOVABLE_BANNER" {{ old('type') == 'ADVERTISEMENT_SMALL_MOVABLE_BANNER' ? 'selected' : '' }}>
                            Advertisement Small Movable Banner
                        </option>
                        <option value="ADVERTISEMENT_BIG_MOVABLE_BANNER" {{ old('type') == 'ADVERTISEMENT_BIG_MOVABLE_BANNER' ? 'selected' : '' }}>
                            Advertisement Big Movable Banner
                        </option>
                    </select>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control mb-2" placeholder="Name" required>
                    <input type="text" name="description" value="{{ old('description') }}"
                           class="form-control mb-2" placeholder="Description">
                    <div class="row">
                        <div class="col-3">
                            <input type="file" name="img" class="form-control mb-2">
                        </div>
                        <div class="col-3">
                        <input type="text" name="link" value="{{ old('link') }}"
                            class="form-control mb-2" placeholder="Link">
                        </div>
                        <div class="col-3">
                            <select name="status" class="form-control mb-3">
                                <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="text" name="holder_type" value="{{ old('holder_type') }}"
                            class="form-control mb-2" placeholder="Type">
                        </div>
                        <div class="col-3" id="colorCodeWrapper" style="display:none;">
                            <input type="color" name="color_code" id="color_code"
                                value="{{ old('color_code', '#000000') }}"
                                class="form-control mb-2" title="Choose Banner Color">
                        </div>
                    </div>
                    <button class="btn btn-success w-100">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function () {
        function toggleColorCode() {
            if ($('#offerType').val() === 'ADVERTISEMENT_SMALL_MOVABLE_BANNER') {
                $('#colorCodeWrapper').show();
            } else {
                $('#colorCodeWrapper').hide();
                $('#color_code').val('#000000'); // reset when hidden
            }
        }

        // run once on load so old('type') on validation-error redirects is respected
        toggleColorCode();

        $('#offerType').on('change', function () {
            toggleColorCode();
        });
    });
</script>
@endsection
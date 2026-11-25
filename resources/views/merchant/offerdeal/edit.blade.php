@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">
        <div class="panel panel-bordered">

            <header class="panel-heading">
                <h3 class="panel-title">
                    <i class="wb-flag"></i> Edit Offer / Deal
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

                <form action="{{ route('merchant.offerdeal.update', $offerDeal->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

           <input type="text" name="tag" value="{{ old('tag', $offerDeal->tag) }}"
                           class="form-control mb-2" placeholder="tag" required>
                    <select name="type" id="offerType" class="form-control mb-3" required>
                        <option value="" disabled {{ old('type', $offerDeal->type) ? '' : 'selected' }}>Select Type</option>
                        <option value="ADVERTISEMENT_SMALL_MOVABLE_BANNER" {{ old('type', $offerDeal->type) == 'ADVERTISEMENT_SMALL_MOVABLE_BANNER' ? 'selected' : '' }}>
                            Advertisement Small Movable Banner
                        </option>
                        <option value="ADVERTISEMENT_BIG_MOVABLE_BANNER" {{ old('type', $offerDeal->type) == 'ADVERTISEMENT_BIG_MOVABLE_BANNER' ? 'selected' : '' }}>
                            Advertisement Big Movable Banner
                        </option>
                    </select>
                    <input type="text" name="name" value="{{ old('name', $offerDeal->name) }}"
                           class="form-control mb-2" placeholder="Name" required>

                    <input type="text" name="description" value="{{ old('description', $offerDeal->description) }}"
                           class="form-control mb-2" placeholder="Description">

                    @if($offerDeal->img)
                        <div class="mb-2">
                            <img src="{{ $offerDeal->img }}" alt="{{ $offerDeal->name }}" width="120" height="80" style="object-fit:cover;border-radius:4px;">
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-3">
                            <input type="file" name="img" class="form-control mb-2">
                        </div>
                        <div class="col-3">
                            <input type="text" name="link" value="{{ old('link', $offerDeal->link) }}"
                               class="form-control mb-2" placeholder="Link">
                        </div>
                        <div class="col-3">
                            <select name="status" class="form-control mb-3">
                                <option value="1" {{ old('status', $offerDeal->status) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ old('status', $offerDeal->status) == 2 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="text" name="holder_type" value="{{ old('holder_type',$offerDeal->holder_type) }}"
                            class="form-control mb-2" placeholder="Type">
                        </div>
                        <div class="col-3" id="colorCodeWrapper" style="{{ old('type', $offerDeal->type) == 'ADVERTISEMENT_SMALL_MOVABLE_BANNER' ? '' : 'display:none;' }}">
                            <input type="color" name="color_code" id="color_code"
                                value="{{ old('color_code', $offerDeal->color_code ?? '#000000') }}"
                                class="form-control mb-2" title="Choose Banner Color">
                        </div>
                    </div>


                    <button class="btn btn-success w-100">Update</button>
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
            }
        }

        // run once on load so the correct state shows for the existing record / old() on validation errors
        toggleColorCode();

        $('#offerType').on('change', function () {
            toggleColorCode();
        });
    });
</script>
@endsection
@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <h3 class="panel-title">
                    <i class="wb-flag"></i> Edit Why Choose Us
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

                <form action="{{ route('merchant.whychooseus.update', $holder->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <input type="text" name="title" value="{{ old('title', $holder->title) }}"
                           class="form-control mb-2" placeholder="Title" required>

                    @if($holder->icon)
                        <div class="mb-2">
                            <img src="{{  get_video($holder->icon, $holder->merchant_id) }}" alt="{{ $holder->title }}"
                                 width="80" height="80" style="object-fit:contain;">
                        </div>
                    @endif

                    <div class="mb-2">
                        <input type="file" name="icon" id="iconInput" accept="image/*" class="form-control">
                        <small class="text-muted">Leave empty to keep the current icon.</small>
                    </div>

                    <div id="iconPreviewWrapper" class="mb-3" style="display:none;">
                        <img id="iconPreview" src="" alt="New icon preview"
                             style="width:80px;height:80px;object-fit:contain;border:1px solid #ddd;border-radius:4px;">
                    </div>

                    <textarea name="description" class="form-control mb-3" rows="3"
                              placeholder="Description" required>{{ old('description', $holder->description) }}</textarea>

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
        $('#iconInput').on('change', function (e) {
            const file = e.target.files[0];

            if (!file) {
                $('#iconPreviewWrapper').hide();
                return;
            }

            const reader = new FileReader();
            reader.onload = function (evt) {
                $('#iconPreview').attr('src', evt.target.result);
                $('#iconPreviewWrapper').show();
            };
            reader.readAsDataURL(file);
        });
    });
</script>
@endsection
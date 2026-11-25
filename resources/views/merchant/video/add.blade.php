@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">
<div class="content-wrapper">
    <div class="container-fluid">

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Add Video</h5>
                <a href="{{ route('merchant.video.index') }}" class="btn btn-secondary btn-sm">
                    Back to List
                </a>
            </div>

            <div class="card-body">
                <form action="{{ route('merchant.video.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="title">Title <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="title"
                                       id="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title') }}"
                                       placeholder="Enter video title">
                                @error('title')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                    <option value="1" {{ old('status') == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea name="description"
                                  id="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="3"
                                  placeholder="Enter description (optional)">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="video">Video File <span class="text-danger">*</span></label>
                        <input type="file"
                               name="video"
                               id="video"
                               accept=".mp4,.mov,.avi,.mkv,.gif"
                               class="form-control @error('video') is-invalid @enderror"
                               onchange="previewMedia(event)"
                               required>
                        <small class="text-muted">Allowed formats: mp4, mov, avi, mkv, gif. Max size: 20MB.</small>
                        @error('video')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3" id="previewWrapper" style="display:none;">
                        <label>Preview</label><br>
                        <video id="videoPreview" width="300" controls style="display:none;"></video>
                        <img id="imagePreview" width="300" style="display:none;" alt="Preview">
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="type">Video Type</label>
                                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                                    <option value="1" {{ old('type') == 1 ? 'selected' : '' }}>Small</option>
                                    <option value="2" {{ old('type') == 2 ? 'selected' : '' }}>Big</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="media_type">Media Type</label>
                                <select name="media_type" id="media_type" class="form-control @error('media_type') is-invalid @enderror" onchange="toggleSegment(event)">
                                    <option value="video" {{ old('media_type') == 'video' ? 'selected' : '' }}>Video</option>
                                    <option value="gif" {{ old('media_type') == 'gif' ? 'selected' : '' }}>GIF</option>
                                </select>
                                @error('media_type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="holder_type">Holder Type</label>
                                <input type="text"
                                       name="holder_type"
                                       id="holder_type"
                                       class="form-control @error('holder_type') is-invalid @enderror"
                                       value="{{ old('holder_type') }}"
                                       placeholder="Enter Holder Type">
                                @error('holder_type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="segmentWrapper" style="display: none;">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="segment_id">Segment</label>
                                <select name="segment_id" id="segment_id" class="form-control @error('segment_id') is-invalid @enderror">
                                    <option value="">--select segment--</option>
                                    @foreach($arr_segment as $key => $value)
                                        <option value="{{ $key }}" {{ old('segment_id') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                                @error('segment_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-5">
                        <i class="fa fa-upload"></i> Upload Video
                    </button>
                    <a href="{{ route('merchant.video.index') }}" class="btn btn-light mt-5">Cancel</a>
                </form>
            </div>
        </div>

    </div>
</div>

    </div>
</div>

<script>
    function previewMedia(event) {
        const file = event.target.files[0];
        const wrapper = document.getElementById('previewWrapper');
        const videoEl = document.getElementById('videoPreview');
        const imgEl = document.getElementById('imagePreview');

        if (!file) return;

        const url = URL.createObjectURL(file);
        const isGif = file.type === 'image/gif' || file.name.toLowerCase().endsWith('.gif');

        if (isGif) {
            videoEl.style.display = 'none';
            videoEl.src = '';
            imgEl.src = url;
            imgEl.style.display = 'block';
        } else {
            imgEl.style.display = 'none';
            imgEl.src = '';
            videoEl.src = url;
            videoEl.style.display = 'block';
        }

        wrapper.style.display = 'block';
    }
    
    function toggleSegment(event) {
        const segmentWrapper = document.getElementById('segmentWrapper');
        segmentWrapper.style.display = event.target.value === 'gif' ? 'flex' : 'none';
    }
    
    document.addEventListener('DOMContentLoaded', function () {
        const mediaTypeSelect = document.getElementById('media_type');
        if (mediaTypeSelect.value === 'gif') {
            document.getElementById('segmentWrapper').style.display = 'flex';
        }
    });
</script>
@endsection
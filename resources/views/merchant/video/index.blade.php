@extends('merchant.layouts.main')

@section('content')
<div class="page">
    <div class="page-content">
<div class="content-wrapper">
    <div class="container-fluid">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert" style="transition: opacity 0.5s ease;">
                {{ session('success') }}
            </div>
        
            <script>
                setTimeout(function () {
                    const alertEl = document.getElementById('successAlert');
                    if (alertEl) {
                        alertEl.style.opacity = '0';
                        setTimeout(function () {
                            alertEl.remove();
                        }, 500); // matches the transition duration
                    }
                }, 5000);
            </script>
        @endif

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
                <h5 class="mb-0">Videos</h5>
                <a href="{{ route('merchant.video.create') }}" class="btn btn-primary btn-sm">
                    + Add Video
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th style="width: 250px;">Video</th>
                                <th style="width: 100px;">Video Type</th>
                                <th style="width: 250px;">Segment</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 150px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($videos as $key => $video)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $video->title }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($video->description, 60) ?: '-' }}</td>
                                    <td>
                                        @if(!empty($video->video_url))
                                            @if($video->media_type === 'gif')
                                                <img src="{{ $video->video_url }}" width="220" height="130" style="object-fit: cover;" alt="{{ $video->title }}">
                                            @else
                                                <video width="220" height="130" controls preload="metadata">
                                                    <source src="{{ $video->video_url }}" type="video/mp4">
                                                    Your browser does not support the video tag.
                                                </video>
                                            @endif
                                        @else
                                            <span class="text-muted">No video</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span>{{ $video->type == 1 ? 'Small' : 'Big' }}</span>
                                    </td>
                                    <td>
                                        {{ $video->Segment?->slag }}
                                    </td>
                                    <td>
                                        @if($video->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('merchant.video.edit', $video->id) }}" class="btn btn-sm btn-info">
                                            Edit
                                        </a>
                                        <form action="{{ route('merchant.video.destroy', $video->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this video?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No videos uploaded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
            </div>
</div>

    </div>
</div>
@endsection
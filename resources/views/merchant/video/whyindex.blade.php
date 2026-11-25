@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <h3 class="panel-title">
                    <i class="wb-flag"></i> Why Choose Us
                </h3>
            </header>
            <div class="panel-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="row">
                    @foreach ($holders as $index => $holder)
                        <div class="col-md-3 mb-3 d-flex">
                            <div class="w-100 d-flex flex-column text-center p-3"
                                 style="border:1px solid #e0e0e0;border-radius:6px;background:#fff;">

                                <div class="mb-2" style="height:70px;display:flex;align-items:center;justify-content:center;">
                                    @if ($holder->icon)
                                        <img src="{{ get_video($holder->icon, $holder->merchant_id) }}" alt="{{ $holder->title }}"
                                             style="max-width:60px;max-height:60px;object-fit:contain;">
                                    @else
                                        <span class="text-muted">No icon</span>
                                    @endif
                                </div>

                                <h5 style="min-height:24px;">{{ $holder->title ?? 'Slot ' . ($index + 1) }}</h5>

                                <p class="text-muted small" style="flex-grow:1;">
                                    {{ $holder->description ?? '-' }}
                                </p>

                                <a href="{{ route('merchant.whychooseus.edit', $holder->id) }}"
                                   class="btn btn-primary btn-sm mt-2">
                                    <i class="wb-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
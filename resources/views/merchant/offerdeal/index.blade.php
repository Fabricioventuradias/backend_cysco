@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        <div class="panel panel-bordered">
            <header class="panel-heading d-flex justify-content-between align-items-center">
                <h3 class="panel-title">
                    <i class="wb-flag"></i> Offers / Deals
                </h3>
                <a href="{{ route('merchant.offerdeal.create') }}" class="btn btn-success btn-sm">
                    <i class="wb-plus"></i> Add Offer / Deal
                </a>
            </header>
            <div class="panel-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                {{--<th>#</th>--}}
                                <th>Image</th>
                                <th>Tag</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Description</th> 
                                <th>Link</th>
                                <th>Holder Type</th>
                                <th>Color</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($offerDeals as $index => $offer)
                                <tr>
                                    {{--<td>{{ $offerDeals->firstItem() + $index }}</td> --}}
                                    <td>
                                        @if ($offer->img)
                                            <img src="{{ get_image($offer->img, 'offer_deals_image',$offer->merchant_id ) }}"
                                                 alt="{{ $offer->name }}"
                                                 style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $offer->tag }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ str_replace('_', ' ', $offer->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $offer->name }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($offer->description, 40) }}</td>
                                    <td>
                                        @if ($offer->link)
                                            <a href="{{ $offer->link }}" target="_blank" rel="noopener">Visit</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $offer->holder_type ?? '-' }}</td>
                                    <td>
                                        @if ($offer->color_code)
                                            <span style="display:inline-block;width:20px;height:20px;
                                                border-radius:3px;border:1px solid #ccc;
                                                background-color: {{ $offer->color_code }};"
                                                title="{{ $offer->color_code }}"></span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($offer->status == 1)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('merchant.offerdeal.edit', $offer->id) }}" class="btn btn-primary btn-sm">
                                            <i class="wb-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No offers/deals found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{-- $offerDeals->links() --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
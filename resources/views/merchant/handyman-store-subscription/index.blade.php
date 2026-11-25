@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')

            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <div class="panel-actions">
                        <div class="btn-group float-right" style="margin:10px">
                            <a href="{{ route('merchant.subscription.handyman.store.create') }}">
                                <button type="button" class="btn btn-icon btn-primary">
                                    <i class="wb-plus"></i> Add Plan
                                </button>
                            </a>
                        </div>
                    </div>
                    <h3 class="panel-title">
                        <i class="icon wb-list" aria-hidden="true"></i> Subscription Plans
                    </h3>
                </header>

                <div class="panel-body container-fluid">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Discount</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>{{ $plan->id }}</td>
                                        <td>{{ $plan->name }}</td>
                                        <td>{{ number_format($plan->price, 2) }}</td>
                                        <td>{{ $plan->duration_days }} days</td>
                                        <td>
                                            @if($plan->discount_type == 1)
                                                <span class="badge badge-info">Fixed: {{ number_format($plan->discount_amount, 2) }}</span>
                                            @else
                                                <span class="badge badge-success">
                                                    {{ $plan->discount_percentage }}%
                                                    
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('merchant.subscription.handyman.store.status', $plan->id) }}">
                                                @if($plan->status == 1)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-danger">Inactive</span>
                                                @endif
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('merchant.subscription.handyman.store.edit', $plan->id) }}"
                                               class="btn btn-sm btn-warning">
                                                <i class="icon wb-edit"></i>
                                            </a>
                                            {{--<a href="{{ route('handyman-store.subscription.delete', $plan->id) }}"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Delete this plan?');">
                                                <i class="icon wb-trash"></i>
                                            </a>--}}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No subscription plans yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $plans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
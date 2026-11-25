@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        @include('merchant.shared.errors-and-messages')
        <div class="panel panel-bordered">
            <header class="panel-heading">
                <div class="panel-actions">
                    <a href="{{ route('merchant.handyman-store.subscription.add') }}"
                       class="btn btn-primary" style="margin:10px;">
                        <i class="fa fa-plus"></i> Add Plan
                    </a>
                </div>
                <h3 class="panel-title">
                    <i class="fa fa-crown"></i> Store Subscription Plans
                </h3>
            </header>
            <div class="panel-body container-fluid">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Service Posts</th>
                                <th>Default</th>
                                <th>Active Stores</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscriptions as $index => $sub)
                                <tr>
                                    <td>{{ $subscriptions->firstItem() + $index }}</td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            @if(!empty($sub->logo))
                                                <img src="{{ get_image($sub->logo, 'icon', $sub->merchant_id,true, false) }}"
                                                     style="width:36px;height:36px;border-radius:8px;object-fit:cover;border:1px solid #ddd;">
                                            @else
                                                <span style="width:36px;height:36px;border-radius:8px;background:#eef2ff;
                                                             display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                    <i class="fa fa-crown" style="color:#5b5ec9;font-size:14px;"></i>
                                                </span>
                                            @endif
                                            <div>
                                                <div style="font-weight:600;">{{ $sub->subscription_name }}</div>
                                                <small class="text-muted">Seq: {{ $sub->sequence }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $sub->amount > 0 ? $sub->amount : 'Free' }}</strong>
                                    </td>
                                    <td>
                                        @if($sub->no_of_service_post == 0)
                                            <span class="badge badge-success">Unlimited</span>
                                        @else
                                            <span class="badge badge-info">{{ $sub->no_of_service_post }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sub->is_default)
                                            <span class="badge badge-primary">
                                                <i class="fa fa-check"></i> Default
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $sub->active_purchases_count }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sub->status == 1)
                                            <a href="{{ route('merchant.handyman-store.subscription.status', [$sub->id, 0]) }}"
                                               class="badge badge-success" style="cursor:pointer;">Active</a>
                                        @else
                                            <a href="{{ route('merchant.handyman-store.subscription.status', [$sub->id, 1]) }}"
                                               class="badge badge-danger" style="cursor:pointer;">Inactive</a>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('merchant.handyman-store.subscription.edit', $sub->id) }}"
                                           class="btn btn-sm btn-warning">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                       
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center" style="padding:40px;">
                                        <i class="fa fa-inbox" style="font-size:40px;color:#ddd;display:block;margin-bottom:10px;"></i>
                                        No subscription plans found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $subscriptions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
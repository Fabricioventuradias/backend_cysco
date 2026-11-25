@extends('merchant.layouts.main')
@section('content')
    <div class="page">
        <div class="page-content">
            @include('merchant.shared.errors-and-messages')

            <div class="panel panel-bordered">
                <header class="panel-heading">
                    <h3 class="panel-title">
                        <i class="icon wb-users" aria-hidden="true"></i> Subscribers
                    </h3>
                </header>

                <div class="panel-body container-fluid">

                    {{-- Filters --}}
                    <form method="GET" class="row mb-3">
                        <div class="col-md-3">
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>Expired</option>
                                <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="payment_status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Payments</option>
                                <option value="1" {{ request('payment_status') == 1 ? 'selected' : '' }}>Paid</option>
                                <option value="2" {{ request('payment_status') == 2 ? 'selected' : '' }}>Pending</option>
                                <option value="3" {{ request('payment_status') == 3 ? 'selected' : '' }}>Failed</option>
                            </select>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Subscriber</th>
                                    <th>Plan</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Amount Paid</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Days Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptions as $sub)
                                    <tr>
                                        <td>{{ $sub->id }}</td>
                                        <td>
                                            {{ optional($sub->User)->name ?? '#'. $sub->user_id }}
                                            @if(optional($sub->User)->first_name)
                                                <br><small class="text-muted">{{ $sub->User->first_name." ". $sub->User->last_name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ optional($sub->plan)->name ?? '—' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($sub->start_date)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($sub->end_date)->format('d M Y') }}</td>
                                        <td>{{ number_format($sub->amount_paid, 2) }}</td>
                                        <td>
                                            @switch($sub->payment_status)
                                                @case(1) <span class="badge badge-success">Paid</span> @break
                                                @case(2) <span class="badge badge-warning">Pending</span> @break
                                                @default <span class="badge badge-danger">Failed</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @switch($sub->status)
                                                @case(1) <span class="badge badge-success">Active</span> @break
                                                @case(2) <span class="badge badge-secondary">Expired</span> @break
                                                @default <span class="badge badge-danger">Cancelled</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @php
                                                $left = now()->startOfDay()
                                                    ->diffInDays(\Carbon\Carbon::parse($sub->end_date), false);
                                            @endphp
                                            {{ $left >= 0 ? $left . ' days' : 'Expired' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No subscribers yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $subscriptions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
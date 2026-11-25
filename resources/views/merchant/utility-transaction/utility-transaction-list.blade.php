@extends('merchant.layouts.main')

@section('content')

<style>
    .table th, .table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .table-responsive {
        overflow-x: auto;
    }
</style>

<div class="page">
    <div class="page-content">

        <div class="panel panel-bordered">
            <header class="panel-heading">
                <h3 class="panel-title">
                    <i class="wb-flag"></i> Transactions List
                </h3>
            </header>

            <div class="panel-body container-fluid">

                <!-- 🔍 Filters -->
                <form method="GET">
                    <div class="row mb-3">

                        <!-- Search -->
                        <div class="col-md-3">
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   class="form-control"
                                   placeholder="Search anything...">
                        </div>

                        <!-- From Date -->
                        <div class="col-md-3">
                            <input type="date"
                                   name="from_date"
                                   value="{{ request('from_date') }}"
                                   class="form-control">
                        </div>

                        <!-- To Date -->
                        <div class="col-md-3">
                            <input type="date"
                                   name="to_date"
                                   value="{{ request('to_date') }}"
                                   class="form-control">
                        </div>
                        
                        <!-- Status Filter -->
                    <div class="col-md-3">
                    <select name="status" class="form-control">

                        <option value="0">All Status</option>

                        <option value="" {{ request('status') === '' ? 'selected' : '' }}>
                            N/A
                        </option>

                        <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>In Progress</option>
                        <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>Completed</option>
                        <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Failed</option>
                        <option value="6" {{ request('status') == 4 ? 'selected' : '' }}>Rejected</option>

                    </select>
                    </div>

                        <!-- Buttons -->
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
                        </div>

                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">

                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Product</th>
                                <th>User Name</th>
                                <th>User Phone</th>
                                <th>Cell/Meterno</th>
                                <th>Ext Txn No</th>
                                <th>Amount</th>
                                <th>Ooze Transaction Id</th>
                                <th>Status</th>
                                <th>Status Message</th>
                                <th>Details</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $statusList = [
                                    1 => 'In Progress',
                                    2 => 'Completed',
                                    3 => 'Failed',
                                    4 => 'Withdraw',
                                    5 => 'Generated',
                                    6 => 'Rejected',
                                ];

                                $productName = [
                                    1 => 'Mascom Pinless Airtime',
                                    4 => 'BPC Electricity',
                                ];
                            @endphp

                            @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->id }}</td>

                                    <td>
                                        {{ $productName[$transaction->product] ?? 'N/A' }}
                                    </td>

                                    <td>
                                        {{ trim(($transaction->user->first_name ?? '') . ' ' . ($transaction->user->last_name ?? '')) ?: 'N/A' }}
                                    </td>

                                    <td>
                                        {{ $transaction->user->UserPhone ?? 'N/A' }}
                                    </td>

                                    <td>{{ $transaction->cell }}</td>
                                    <td>{{ $transaction->ext_txn_no }}</td>
                                    <td>{{ $transaction->amount }}</td>
                                    <td>{{ $transaction->trans_id }}</td>

                                    <td>
                                        <span class="badge 
                                            @if($transaction->transaction_status == 2) badge-success
                                            @elseif($transaction->transaction_status == 3) badge-danger
                                            @else badge-secondary
                                            @endif">
                                            {{ $statusList[$transaction->transaction_status] ?? 'N/A' }}
                                        </span>
                                    </td>
<td title="{{ $transaction->msg ?? 'N/A' }}">
    {{ \Illuminate\Support\Str::limit($transaction->msg ?? 'N/A', 30) }}
</td>

                          <td title="{{ is_array($transaction->details) ? json_encode($transaction->details) : $transaction->details }}">
    {{ \Illuminate\Support\Str::limit(
        is_array($transaction->details)
            ? json_encode($transaction->details)
            : $transaction->details,
        40
    ) }}
</td>

                                    <td>
                                        {{ $transaction->created_at ? $transaction->created_at->format('d-m-Y H:i') : 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">
                                        No records found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <!-- 📄 Pagination -->
                <div class="mt-3">
                    {{ $transactions->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
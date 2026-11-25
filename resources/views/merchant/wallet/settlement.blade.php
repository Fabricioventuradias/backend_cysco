@extends('merchant.layouts.main')
@section('content')
<div class="page">
    <div class="page-content">
        <div class="container py-4">
            <h4 class="mb-4">Wallet Settlement</h4>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($error)
                <div class="alert alert-warning">{{ $error }}</div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Current Wallet Balance</h6>
                    @if($balance)
                        <h2 class="mb-0">{{ number_format($balance['wallet_balance'], 2) }}</h2>
                        <small class="text-muted">Last transaction: {{ $balance['last_transaction_date'] }}</small>
                    @else
                        <p class="mb-0 text-muted">Balance unavailable</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Settle Amount</h6>
                    <form action="{{ route('merchant.wallet.settle') }}" method="POST" id="settleForm">
                        @csrf
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" min="1"
                                   name="amount" id="amount"
                                   class="form-control" placeholder="Enter amount" required
                                   {{ !$balance ? 'disabled' : '' }}>
                            <small class="text-muted d-block mt-1" id="feeBreakdown"></small>
                        </div>
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
    Submit Settlement
</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('amount')?.addEventListener('input', function () {
    updateBreakdown();
});

function updateBreakdown() {
    const amountInput = document.getElementById('amount');
    const breakdown = document.getElementById('feeBreakdown');
    const submitBtn = document.querySelector('#settleForm button[type="submit"]');
    const balance = parseFloat("{{ $balance['wallet_balance'] ?? 0 }}");
    const amount = parseFloat(amountInput.value) || 0;
    const ubxFee = +(amount * 0.01).toFixed(2);
    const totalCollected = +(amount + ubxFee).toFixed(2);

    if (amount <= 0) {
        breakdown.innerText = '';
        breakdown.classList.remove('text-danger');
        breakdown.classList.add('text-muted');
        submitBtn.disabled = true;
        return false;
    }

    if (totalCollected > balance) {
        breakdown.innerText = "Withdrawal amount + UBX fee exceeds your available wallet balance.";
        breakdown.classList.remove('text-muted');
        breakdown.classList.add('text-danger');
        submitBtn.disabled = true;
        return false;
    }

    breakdown.innerText = `UBX fee: ${ubxFee} | Total deducted from wallet: ${totalCollected}`;
    breakdown.classList.remove('text-danger');
    breakdown.classList.add('text-muted');
    submitBtn.disabled = false;
    return true;
}

document.getElementById('settleForm')?.addEventListener('submit', function (e) {
    const isValid = updateBreakdown();
    if (!isValid) {
        e.preventDefault();
    }
});
</script>
@endsection
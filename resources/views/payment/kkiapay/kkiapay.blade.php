<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Redirecting to Payment...</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; height: 100vh; display: flex; justify-content: center; align-items: center; }
        .loading-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 8px rgba(0,0,0,0.1); text-align: center; }
        .spinner { width: 40px; height: 40px; border: 4px solid #ccc; border-top: 4px solid #28a745; border-radius: 50%; margin: 0 auto 15px; animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loading-box" id="loadingBox">
        <div class="spinner"></div>
        <p>Redirecting to secure payment gateway...</p>
    </div>

    <script src="https://cdn.kkiapay.me/k.js"></script>
    <script>
        window.onload = function () {
            setTimeout(() => {
                openKkiapayWidget({
                    amount: {{ (int) $amount }},
                    key: "{{ $public_key }}",
                    sandbox: {{ $sandbox }},
                    position: "center",
                    name: "{{ $customerFirstName }} {{ $customerLastName }}",
                    email: "{{ $customerEmail }}",
                    phone: "{{ $customerPhone }}",
                    data: JSON.stringify({ orderReference: "{{ $orderReference }}" }),
                    callback: ""
                });
            }, 1);
        };

        addSuccessListener(function (response) {
            document.getElementById('loadingBox').innerHTML =
                '<div class="spinner"></div><p>Verifying payment...</p>';

            fetch("{{ $verifyUrl }}", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    transaction_id: response.transactionId,
                    orderReference: "{{ $orderReference }}"
                })
            })
            .then(res => res.json())
            .then(result => {
                window.location.href = result.redirect;
            })
            .catch(() => {
                window.location.href = "{{ $fail_url }}";
            });
        });

        addFailedListener(function (error) {
            console.error(error);
            window.location.href = "{{ $fail_url }}";
        });
    </script>
</body>
</html>
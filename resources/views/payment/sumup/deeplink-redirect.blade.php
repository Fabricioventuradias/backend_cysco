<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
    <p>{{ $status === 'success' ? 'Payment successful' : 'Payment failed' }}. Redirecting…</p>
    <script>
        window.location.href = @json($deeplink);
    </script>
</body>
</html>
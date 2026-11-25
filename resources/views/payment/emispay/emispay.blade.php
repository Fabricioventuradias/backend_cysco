<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EMIS Payment</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        iframe { width: 100%; height: 100vh; border: none; }
    </style>
</head>
<body>

<iframe id="gpo-frame" src="{{ $frame_url }}"></iframe>

<script>
    var TRANS_ID   = @json($trans_id);
    var SUCCESS_URL = @json($success_url);
    var FAIL_URL    = @json($fail_url);

    window.addEventListener('message', function (event) {
        // Only trust messages from GPO's real domain, per §2.1.3.2.1
        if (event.origin !== 'https://gpo.emis.co.ao') { return; }

        console.log('Received from GPO:', event.data);

        // Bridge to native app WebViews so the app can close this screen natively
        if (window.AndroidBridge && typeof window.AndroidBridge.onPaymentResult === 'function') {
            window.AndroidBridge.onPaymentResult(JSON.stringify(event.data));
        }
        if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.paymentResult) {
            window.webkit.messageHandlers.paymentResult.postMessage(event.data);
        }

        // Fallback for a plain browser context (no native bridge): redirect ourselves.
        // NOTE: confirm the real shape of event.data with EMIS/testing — assuming
        // it carries an "accepted"/"status" flag similar to the callback payload.
        var accepted = event.data && (event.data.accepted === true || event.data.status === 'ACCEPTED');
        var rejected = event.data && (event.data.accepted === false || event.data.status === 'REJECTED');

        if (accepted) {
            window.location.href = SUCCESS_URL + '?reference=' + encodeURIComponent(TRANS_ID);
        } else if (rejected) {
            window.location.href = FAIL_URL + '?reference=' + encodeURIComponent(TRANS_ID);
        }
        // If event.data is empty (timeout with the network per the manual), do nothing —
        // rely on the server-to-server callbackUrl as the source of truth.
    }, false);
</script>

</body>
</html>
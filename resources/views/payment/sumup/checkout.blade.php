<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Add Payment Method</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: -apple-system, Roboto, sans-serif;
        }
        #wrapper {
            padding: 16px;
        }
        #status {
            text-align: center;
            padding: 24px;
            color: #555;
            font-size: 14px;
        }
        #sumup-card {
            display: none;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <div id="status">Loading payment form&hellip;</div>
        <div id="sumup-card"></div>
    </div>

    <script src="https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js"></script>
    <script>
        const CHECKOUT_ID = @json($checkoutId);
        const statusEl = document.getElementById('status');
        const cardEl = document.getElementById('sumup-card');

        function sendToApp(payload) {
            console.log(payload);
            const msg = JSON.stringify(payload);
            try {
                if (window.ReactNativeWebView) {
                    // React Native WebView
                    window.ReactNativeWebView.postMessage(msg);
                } else if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.sumup) {
                    // iOS native WKWebView (message handler name: "sumup")
                    window.webkit.messageHandlers.sumup.postMessage(msg);
                } else if (window.Android && window.Android.onSumUpResult) {
                    // Android native WebView (JavascriptInterface name: "Android")
                    window.Android.onSumUpResult(msg);
                } else {
                    console.log('SumUp result (no native bridge found):', msg);
                }
            } catch (e) {
                console.error('Failed to post message to app', e);
            }
        }

        if (!CHECKOUT_ID) {
            statusEl.textContent = 'Missing checkout reference.';
            sendToApp({ type: 'error', body: { message: 'Missing checkout_id' } });
        } else {
            statusEl.style.display = 'none';
            cardEl.style.display = 'block';

            SumUpCard.mount({
                id: 'sumup-card',
                checkoutId: CHECKOUT_ID,
                showFooter: false,
                showAmount: false,
                locale: 'en-GB',
                @if($enableWallets)
                    googlePay: {
                        merchantId: 'TEST1235',
                        merchantName: 'Apporio Business',
                    },
                @endif
                onResponse: function (type, body) {
                    console.log('SumUp onResponse', type, body);
                    if (type === 'success') {
                        const card = body.card || body.payment_instrument || {};
                        sendToApp({
                            type: type,
                            checkout_id: '{{ $checkoutId }}',
                            card_holder: card.name || null,
                            card_number: card.last_4_digits || card.last4 || null,
                            card_type: card.type || card.brand || null,
                            exp_month: card.expiry_month || null,
                            exp_year: card.expiry_year || null,
                        });
                    } else {
                        sendToApp({ type: type, body: body });
                    }
                },
                onLoad: function () {
                    console.log('SumUp widget mounted');
                },
            });
        }
    </script>
</body>
</html>
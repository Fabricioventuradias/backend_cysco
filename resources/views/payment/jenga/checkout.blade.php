<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to Jenga PGW...</title>
</head>
<body>
    <p>Please wait, redirecting to secure payment page...</p>

    <form id="submitcheckout" action="{{ $checkoutUrl }}" method="POST">
        <input type="hidden" name="token" value="{{ $token }}" />
        <input type="hidden" name="merchantCode" value="{{ $merchantCode }}" />
        <input type="hidden" name="currency" value="{{ $currency }}" />
        <input type="hidden" name="orderAmount" value="{{ $orderAmount }}" />
        <input type="hidden" name="orderReference" value="{{ $orderReference }}" />
        <input type="hidden" name="productType" value="{{ $productType }}" />
        <input type="hidden" name="productDescription" value="{{ $productDescription }}" />
        <input type="hidden" name="paymentTimeLimit" value="{{ $paymentTimeLimit }}" />
        <input type="hidden" name="customerFirstName" value="{{ $customerFirstName }}" />
        <input type="hidden" name="customerLastName" value="{{ $customerLastName }}" />
        <input type="hidden" name="customerPostalCodeZip" value="{{ $customerPostalCodeZip }}" />
        <input type="hidden" name="customerAddress" value="{{ $customerAddress }}" />
        <input type="hidden" name="customerEmail" value="{{ $customerEmail }}" />
        <input type="hidden" name="customerPhone" value="{{ $customerPhone }}" />
        <input type="hidden" name="extraData" value="{{ $extraData }}" />
        <input type="hidden" name="callbackUrl" value="{{ $callbackUrl }}" />
        <input type="hidden" name="countryCode" value="{{ $countryCode }}" />
        <input type="hidden" name="secondaryReference" value="{{ $secondaryReference }}" />
        <input type="hidden" name="signature" value="{{ $signature }}" />
    </form>

    <script>
        document.getElementById('submitcheckout').submit();
    </script>
</body>
</html>
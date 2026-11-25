<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Page</title>
     <meta http-equiv="Content-Security-Policy" 
              content="
                       style-src 'self' https://eu-test.oppwa.com 'unsafe-inline' ;
                       frame-src 'self' https://eu-test.oppwa.com;
                       script-src 'self' https://eu-test.oppwa.com 'nonce-{{$nonce}}' ;
                       connect-src 'self' https://eu-test.oppwa.com;
                       img-src 'self' https://eu-test.oppwa.com;
                       ">
    <script
        src="https://eu-test.oppwa.com/v1/paymentWidgets.js?checkoutId={{ $checkout_id }}"
        integrity="{{ $integrity }}"
        crossorigin="anonymous">
    </script>
     <script nonce="{{$nonce}}">
            var wpwlOptions = {
                style:"card",
            }
        </script>
</head>  
<body>
      <form action="{{route('hyperpay-success')}}" class="paymentWidgets" data-brands="MADA VISA MASTER"></form>
</body>



</html>
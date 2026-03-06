<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ECPay Redirect</title>
</head>
<body>
    <form id="ecpay_checkout_form" method="post" action="{{ $action }}">
        @foreach ($parameters as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        document.getElementById('ecpay_checkout_form').submit();
    </script>
</body>
</html>

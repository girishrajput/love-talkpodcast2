<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment | Love Talk Podcast</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { background-color: #0b0f19; color: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .spinner-border { color: #e11d48; width: 3rem; height: 3rem; }
    </style>
</head>
<body onload="startPayment()">
    <div class="text-center p-4">
        <div class="spinner-border mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="fw-bold">Opening Razorpay Checkout...</h4>
        <p class="text-secondary small">Please do not refresh or close this window.</p>

        <form name="razorpayForm" action="{{ route('razorpay.success') }}" method="POST">
            @csrf
            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
            <input type="hidden" name="razorpay_signature" id="razorpay_signature">
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="name" value="{{ $name }}">
            <input type="hidden" name="amount" value="{{ $amount }}">
        </form>

        <div class="mt-4">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="startPayment()">Re-open Razorpay</button>
            <a href="{{ url('/razorpay') }}" class="btn btn-link text-secondary btn-sm">Cancel</a>
        </div>
    </div>

    <script>
        function startPayment() {
            var isMock = "{{ $razorpayKey }}" === 'rzp_test_lovetalk2026' || "{{ $razorpayKey }}".includes('YOUR_');

            if (isMock) {
                // Auto-complete mock test mode
                setTimeout(function() {
                    document.getElementById('razorpay_payment_id').value = 'pay_test_' + Date.now();
                    document.getElementById('razorpay_order_id').value = "{{ $orderId }}";
                    document.getElementById('razorpay_signature').value = 'mock_sig_' + Date.now();
                    document.forms['razorpayForm'].submit();
                }, 1000);
                return;
            }

            var options = {
                "key": "{{ $razorpayKey }}",
                "amount": "{{ $amount * 100 }}",
                "currency": "INR",
                "name": "Love Talk Podcast",
                "description": "Podcast Membership Payment",
                "image": "{{ asset('images/favicon.png') }}",
                "order_id": "{{ $orderId }}",
                "handler": function (response) {
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    document.getElementById('razorpay_order_id').value = response.razorpay_order_id || "{{ $orderId }}";
                    document.getElementById('razorpay_signature').value = response.razorpay_signature || '';
                    document.forms['razorpayForm'].submit();
                },
                "prefill": {
                    "name": "{{ $name }}",
                    "email": "{{ $email }}",
                    "contact": "{{ $phone }}"
                },
                "theme": {
                    "color": "#e11d48"
                }
            };
            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (resp) {
                alert('Payment Failed: ' + (resp.error.description || 'Unknown error'));
                window.location.href = "{{ url('/razorpay') }}";
            });
            rzp.open();
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Razorpay Membership Payment | Love Talk Podcast</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #0b0f19; color: #f1f5f9; min-height: 100vh; }
        .card { background-color: #111827; border: 1px solid #1f2937; border-radius: 1rem; }
        .form-control { background-color: #1f2937; border: 1px solid #374151; color: #fff; }
        .form-control:focus { background-color: #1e293b; color: #fff; border-color: #e11d48; box-shadow: 0 0 0 0.25rem rgba(225, 29, 72, 0.25); }
        .btn-primary { background: linear-gradient(135deg, #e11d48, #f43f5e); border: none; font-weight: 700; }
        .btn-primary:hover { background: linear-gradient(135deg, #be123c, #e11d48); }
    </style>
</head>
<body class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('payment'))
                <div class="card mb-4">
                    <div class="card-header bg-success text-white fw-bold">
                        Payment & Membership Details
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Payment ID:</strong> <code class="text-warning">{{ session('payment')->razorpay_payment_id }}</code></p>
                        <p class="mb-1"><strong>Amount:</strong> ₹{{ session('payment')->amount }}</p>
                        <p class="mb-0 text-success fw-bold">Status: Active Premium Member</p>
                    </div>
                </div>
            @endif

            <div class="card shadow-lg p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="{{ asset('images/favicon.png') }}" width="42" height="42" alt="Logo" class="rounded-circle">
                    <div>
                        <h4 class="mb-0 fw-bold">Love Talk Podcast</h4>
                        <small class="text-secondary">Razorpay Membership Checkout</small>
                    </div>
                </div>

                <form action="{{ route('razorpay.payment') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label text-secondary small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" value="{{ auth()->user()->name ?? 'Girish Kumar' }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label text-secondary small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ auth()->user()->email ?? 'girishkumarlodhi7@gmail.com' }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label text-secondary small fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="9876543210" required>
                    </div>
                    <div class="mb-4">
                        <label for="amount" class="form-label text-secondary small fw-bold">Membership Amount (INR)</label>
                        <select name="amount" class="form-control">
                            <option value="99">Youth Plan — ₹99 / Year</option>
                            <option value="399">Professional Plan — ₹399 / Year</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-3">Proceed to Pay via Razorpay</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="{{ url('/membership') }}" class="text-secondary small text-decoration-none">&larr; Return to Main App</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Love Talk Podcast') }} | Tim & Chels</title>

        <base href="{{ url('/') }}/">

        <!-- Fonts & Icons -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

        <!-- Razorpay Checkout Script -->
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

        <!-- Scripts & Styles via Vite -->
        @viteReactRefresh
        @vite(['resources/js/app.tsx'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900 dark:bg-gray-950 dark:text-slate-100 min-h-screen selection:bg-rose-500 selection:text-white">
        <div id="root"></div>
    </body>
</html>

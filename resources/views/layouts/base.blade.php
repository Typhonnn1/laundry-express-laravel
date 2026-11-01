<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Laundry Express - Layanan Cuci Kilat & Berkualitas')</title>
    <meta name="description" content="@yield('meta_description', 'Layanan laundry express kiloan, satuan, dan dry cleaning cepat, bersih, wangi, dan terpercaya.')">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CDN CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome 6.5.2 CDN CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Custom Brand Responsive Styling -->
    <style>
        :root {
            --bs-font-sans-serif: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --bs-primary: #0d6efd;
            --bs-primary-rgb: 13, 110, 253;
            --le-brand-blue: #1e40af;
            --le-brand-cyan: #06b6d4;
            --le-brand-gradient: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #0284c7 100%);
            --le-card-border-radius: 1rem;
        }

        body {
            font-family: var(--bs-font-sans-serif);
            color: #1e293b;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .bg-hero-gradient {
            background: var(--le-brand-gradient);
        }

        .card {
            border-radius: var(--le-card-border-radius);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .hover-shadow-lg:hover {
            transform: translateY(-4px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.08) !important;
        }

        .rounded-4 {
            border-radius: 1rem !important;
        }

        .badge {
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .btn-pill {
            border-radius: 50rem;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Print Media Styling for Nota / Receipts */
        @media print {
            .d-print-none {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    @yield('body')

    <!-- Bootstrap 5.3.3 Bundle JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    @stack('scripts')
</body>
</html>

@extends('layouts.base')

@section('body')
    <!-- Customer Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm py-3 d-print-none">
        <div class="container">
            <!-- Brand Logo & Name -->
            <a class="navbar-brand d-flex align-items-center fw-bold text-primary" href="{{ url('/') }}">
                <span class="bg-primary text-white rounded-3 p-2 me-2 shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-shirt"></i>
                </span>
                <span class="fs-4 tracking-tight">Laundry Express</span>
            </a>

            <!-- Mobile Hamburger Toggle -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCustomer" aria-controls="navbarCustomer" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarCustomer">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold px-3 text-secondary {{ request()->is('/') && !request()->has('search') ? 'active text-primary fw-bold' : '' }}" href="{{ url('/') }}#hero">
                            <i class="fa-solid fa-house me-1"></i> Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold px-3 text-secondary" href="{{ url('/') }}#layanan">
                            <i class="fa-solid fa-tags me-1"></i> Layanan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold px-3 text-secondary" href="{{ url('/') }}#form-pemesanan">
                            <i class="fa-solid fa-basket-shopping me-1"></i> Pesan Sekarang
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold px-3 text-secondary {{ request()->has('search') ? 'active text-primary fw-bold' : '' }}" href="{{ url('/') }}#lacak-pesanan">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Lacak Pesanan
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        @auth
                            <a class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-semibold" href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin') }}">
                                <i class="fa-solid fa-gauge-high me-1"></i> Dashboard Admin
                            </a>
                        @else
                            <a class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-semibold" href="{{ Route::has('login') ? route('login') : url('/login') }}">
                                <i class="fa-solid fa-user-shield me-1"></i> Masuk Admin
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Alert Messages Container -->
    <div class="container mt-3 d-print-none">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center rounded-3 mb-3" role="alert">
                <i class="fa-solid fa-circle-check fs-5 text-success me-2"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center rounded-3 mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation fs-5 text-danger me-2"></i>
                <div class="flex-grow-1">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3" role="alert">
                <div class="d-flex align-items-center mb-1">
                    <i class="fa-solid fa-triangle-exclamation fs-5 text-danger me-2"></i>
                    <strong class="fw-semibold">Mohon periksa kembali input Anda:</strong>
                </div>
                <ul class="mb-0 ps-4 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <!-- Main Content Slot -->
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Comprehensive Customer Footer -->
    <footer class="bg-dark text-white pt-5 pb-4 mt-5 d-print-none">
        <div class="container">
            <div class="row g-4">
                <!-- Col 1: Brand & Overview -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <span class="bg-primary text-white rounded-3 p-2 me-2 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="fa-solid fa-shirt"></i>
                        </span>
                        <h5 class="fw-bold mb-0 text-white">Laundry Express</h5>
                    </div>
                    <p class="text-secondary small mb-3">
                        Layanan cuci kilat modern dengan standar higienis tinggi, deterjen berkualitas, dan garansi wangi serta rapi untuk setiap lembar pakaian Anda.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-brands fa-whatsapp text-white"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-brands fa-instagram text-white"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-brands fa-facebook-f text-white"></i></a>
                    </div>
                </div>

                <!-- Col 2: Navigasi Cepat -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-white mb-3">Navigasi</h6>
                    <ul class="list-unstyled text-secondary small d-flex flex-column gap-2 mb-0">
                        <li><a href="{{ url('/') }}#hero" class="text-secondary text-decoration-none hover-white">Beranda</a></li>
                        <li><a href="{{ url('/') }}#layanan" class="text-secondary text-decoration-none hover-white">Daftar Layanan</a></li>
                        <li><a href="{{ url('/') }}#form-pemesanan" class="text-secondary text-decoration-none hover-white">Form Pemesanan</a></li>
                        <li><a href="{{ url('/') }}#lacak-pesanan" class="text-secondary text-decoration-none hover-white">Lacak Pesanan</a></li>
                        <li><a href="{{ Route::has('login') ? route('login') : url('/login') }}" class="text-secondary text-decoration-none hover-white">Login Petugas</a></li>
                    </ul>
                </div>

                <!-- Col 3: Jam Operasional -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white mb-3">Jam Operasional</h6>
                    <ul class="list-unstyled text-secondary small d-flex flex-column gap-2 mb-0">
                        <li><i class="fa-solid fa-clock text-primary me-2"></i><strong>Senin - Minggu:</strong> 07.00 - 21.00 WIB</li>
                        <li><i class="fa-solid fa-bolt text-danger me-2"></i><strong>Layanan Express:</strong> Maks. order 17.00 WIB</li>
                        <li><i class="fa-solid fa-truck-fast text-success me-2"></i><strong>Antar-Jemput:</strong> Siap setiap hari</li>
                    </ul>
                </div>

                <!-- Col 4: Kontak & Alamat -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white mb-3">Kontak & Lokasi</h6>
                    <ul class="list-unstyled text-secondary small d-flex flex-column gap-2 mb-0">
                        <li><i class="fa-solid fa-location-dot text-danger me-2"></i>Jl. Cepat Bersih No. 88, Jakarta Selatan</li>
                        <li><i class="fa-brands fa-whatsapp text-success me-2"></i>WhatsApp: 0812-3456-7890</li>
                        <li><i class="fa-solid fa-envelope text-info me-2"></i>admin@laundry.test</li>
                    </ul>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <span class="text-secondary small">&copy; {{ date('Y') }} <strong>Laundry Express</strong>. Seluruh Hak Cipta Dilindungi.</span>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small">
                        <i class="fa-solid fa-shield-halved me-1"></i> Sistem Cuci Kilat Terpercaya
                    </span>
                </div>
            </div>
        </div>
    </footer>

    @stack('modals')
@endsection

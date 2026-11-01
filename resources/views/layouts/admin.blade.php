@extends('layouts.base')

@section('body')
<div class="d-flex min-vh-100 flex-column flex-lg-row">
    <!-- Sidebar Navigation (Responsive Offcanvas on Mobile, Fixed Sidebar on Large Screens) -->
    <div class="offcanvas-lg offcanvas-start bg-dark text-white d-flex flex-column flex-shrink-0 p-3 p-lg-4" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel" style="width: 270px; z-index: 1045;">
        <!-- Brand Header -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin') }}" class="d-flex align-items-center text-white text-decoration-none">
                <span class="bg-primary text-white rounded-3 p-2 me-2 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                    <i class="fa-solid fa-shirt fs-5"></i>
                </span>
                <div>
                    <span class="fs-5 fw-bold d-block lh-1">Laundry Express</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small mt-1" style="font-size: 0.65rem; letter-spacing: 0.05em;">ADMIN CONSOLE</span>
                </div>
            </a>
            <!-- Mobile Offcanvas Close Button -->
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button>
        </div>

        <hr class="border-secondary my-2">

        <!-- Navigation Links -->
        <ul class="nav nav-pills flex-column mb-auto gap-1">
            <li class="nav-item">
                <a href="{{ Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin') }}" class="nav-link {{ request()->is('admin') ? 'active bg-primary' : 'text-white-50 hover-white' }} fw-semibold rounded-3 py-2 px-3">
                    <i class="fa-solid fa-gauge-high me-2 text-center" style="width: 20px;"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ Route::has('admin.services.index') ? route('admin.services.index') : url('/admin/layanan') }}" class="nav-link {{ request()->is('admin/layanan*') ? 'active bg-primary' : 'text-white-50 hover-white' }} fw-semibold rounded-3 py-2 px-3">
                    <i class="fa-solid fa-tags me-2 text-center" style="width: 20px;"></i> Layanan
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : url('/admin/pesanan') }}" class="nav-link {{ request()->is('admin/pesanan*') ? 'active bg-primary' : 'text-white-50 hover-white' }} fw-semibold rounded-3 py-2 px-3">
                    <i class="fa-solid fa-basket-shopping me-2 text-center" style="width: 20px;"></i> Pesanan
                </a>
            </li>

            <li class="nav-item mt-3 mb-1">
                <span class="text-uppercase text-secondary fw-bold px-3" style="font-size: 0.7rem; letter-spacing: 0.08em;">Akses Publik</span>
            </li>
            <li class="nav-item">
                <a href="{{ url('/') }}" target="_blank" class="nav-link text-white-50 hover-white fw-semibold rounded-3 py-2 px-3">
                    <i class="fa-solid fa-globe me-2 text-center" style="width: 20px;"></i> Lihat Web
                    <i class="fa-solid fa-arrow-up-right-from-square ms-auto small text-secondary"></i>
                </a>
            </li>
        </ul>

        <hr class="border-secondary my-3">

        <!-- User Profile & Quick Logout in Sidebar -->
        <div class="d-flex align-items-center justify-content-between pt-1">
            <div class="d-flex align-items-center text-truncate me-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2 flex-shrink-0 shadow-sm" style="width: 38px; height: 38px; font-weight: 700;">
                    {{ strtoupper(substr(Auth::user()->name ?? Auth::user()->username ?? 'A', 0, 1)) }}
                </div>
                <div class="text-truncate">
                    <span class="d-block text-white fw-semibold small text-truncate">{{ Auth::user()->name ?? Auth::user()->username ?? 'Admin' }}</span>
                    <span class="badge bg-secondary-subtle text-secondary small" style="font-size: 0.65rem;">Petugas Laundry</span>
                </div>
            </div>
            <form action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Keluar / Logout" style="width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Main Admin Workspace -->
    <div class="d-flex flex-column flex-grow-1 min-vw-0">
        <!-- Top Sticky Header -->
        <header class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4 py-3 sticky-top">
            <div class="container-fluid p-0">
                <!-- Left: Sidebar Toggle on Mobile + Current View Title -->
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary btn-sm d-lg-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
                        <i class="fa-solid fa-bars fs-6"></i>
                    </button>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">
                            @yield('header_title', 'Dashboard')
                        </h5>
                    </div>
                </div>

                <!-- Right: Admin Metadata, Web Link & Logout -->
                <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto">
                    <!-- Admin Username Badge -->
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center">
                        <i class="fa-solid fa-user-shield me-1"></i> {{ Auth::user()->name ?? Auth::user()->username ?? 'Admin' }}
                    </span>

                    <!-- Lihat Web Shortcut -->
                    <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-none d-md-inline-flex align-items-center">
                        <i class="fa-solid fa-globe me-1"></i> Lihat Web
                    </a>

                    <!-- Logout Button -->
                    <form action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-semibold d-inline-flex align-items-center">
                            <i class="fa-solid fa-right-from-bracket me-1"></i>
                            <span class="d-none d-sm-inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Flash Alert Messages Container -->
        <div class="container-fluid px-4 mt-3">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center rounded-3 mb-3" role="alert">
                    <i class="fa-solid fa-circle-check fs-5 text-success me-2 flex-shrink-0"></i>
                    <div class="flex-grow-1">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center rounded-3 mb-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation fs-5 text-danger me-2 flex-shrink-0"></i>
                    <div class="flex-grow-1">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fa-solid fa-triangle-exclamation fs-5 text-danger me-2 flex-shrink-0"></i>
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
        <main class="flex-grow-1 px-4 py-3">
            @yield('content')
        </main>

        <!-- Admin Footer -->
        <footer class="bg-white border-top py-3 px-4 mt-auto">
            <div class="container-fluid p-0 d-flex flex-column flex-md-row justify-content-between align-items-center">
                <span class="text-secondary small">&copy; {{ date('Y') }} <strong>Laundry Express</strong>. Admin Management Console.</span>
                <span class="badge bg-light text-secondary border small mt-2 mt-md-0">
                    <i class="fa-solid fa-shield-halved text-primary me-1"></i> Versi 1.0.0
                </span>
            </div>
        </footer>
    </div>
</div>

@stack('modals')
@endsection

@extends('layouts.admin')

@section('title', 'Dashboard Utama - Laundry Express Admin')
@section('header_title', 'Ringkasan Dashboard')

@section('content')
<!-- Welcome Greeting & Operational Timestamp Banner -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white overflow-hidden position-relative">
    <div class="card-body p-4 position-relative" style="z-index: 1;">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <span class="badge bg-white text-primary fw-bold px-3 py-1 rounded-pill mb-2">
                    <i class="fa-solid fa-circle-check me-1"></i> Sistem Aktif & Terhubung
                </span>
                <h3 class="fw-bold mb-1">Selamat Datang, {{ Auth::user()->name ?? Auth::user()->username ?? 'Administrator' }}!</h3>
                <p class="mb-0 text-white-50">
                    Berikut adalah ringkasan performa finansial, volume antrean cucian, dan transaksi pesanan terkini di Laundry Express.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 rounded-pill px-3 py-2 small">
                    <i class="fa-regular fa-calendar-days me-1"></i> {{ date('d F Y') }}
                </span>
            </div>
        </div>
    </div>
    <!-- Decorative Watermark Icon -->
    <i class="fa-solid fa-shirt position-absolute text-white opacity-10" style="font-size: 10rem; right: -20px; bottom: -30px; pointer-events: none;"></i>
</div>

<!-- 5 Stat Cards Overview Row -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Pesanan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 hover-shadow-lg transition">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">Total Pesanan</span>
                    <span class="bg-primary-subtle text-primary rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fa-solid fa-basket-shopping fs-5"></i>
                    </span>
                </div>
                <h2 class="fw-bold text-dark mb-1">{{ number_format($totalOrders) }}</h2>
                <div class="d-flex align-items-center text-muted small">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">Semua Status</span>
                    <span>Transaksi masuk</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Pendapatan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100 hover-shadow-lg transition">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">Total Pendapatan</span>
                    <span class="bg-success-subtle text-success rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fa-solid fa-wallet fs-5"></i>
                    </span>
                </div>
                <h2 class="fw-bold text-success mb-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h2>
                <div class="d-flex align-items-center text-muted small">
                    <span class="badge bg-success-subtle text-success border border-success-subtle me-1">Akumulasi Omset</span>
                    <span>Dari pesanan aktif</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Antrean -->
    <div class="col-12 col-sm-4 col-xl-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 hover-shadow-lg transition border-start border-4 border-warning">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">Antrean</span>
                    <span class="bg-warning-subtle text-warning-emphasis rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-clock fs-5"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-warning-emphasis mb-1">{{ number_format($countAntrean) }}</h3>
                <span class="text-muted small">Menunggu dicuci</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Dicuci -->
    <div class="col-12 col-sm-4 col-xl-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 hover-shadow-lg transition border-start border-4 border-info">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">Dicuci</span>
                    <span class="bg-info-subtle text-info-emphasis rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-soap fs-5"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-info-emphasis mb-1">{{ number_format($countDicuci) }}</h3>
                <span class="text-muted small">Sedang diproses</span>
            </div>
        </div>
    </div>

    <!-- Card 5: Selesai -->
    <div class="col-12 col-sm-4 col-xl-2">
        <div class="card border-0 shadow-sm rounded-4 h-100 hover-shadow-lg transition border-start border-4 border-success">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-bold text-uppercase">Selesai</span>
                    <span class="bg-success-subtle text-success rounded-3 p-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-success mb-1">{{ number_format($countSelesai) }}</h3>
                <span class="text-muted small">Siap diambil/antar</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Section: Recent Transactions Table & Quick Actions -->
<div class="row g-4">
    <!-- Left Column: Recent Transactions Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <span class="bg-primary text-white rounded-3 p-2 me-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-receipt small"></i>
                    </span>
                    <h5 class="fw-bold mb-0 text-dark">Transaksi Pesanan Terbaru</h5>
                </div>
                <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : url('/admin/pesanan') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                    Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">ID Pesanan</th>
                                <th class="py-3">Pelanggan</th>
                                <th class="py-3">No. WhatsApp</th>
                                <th class="py-3">Total Harga</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Tanggal</th>
                                <th class="pe-4 py-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-light text-primary border border-primary-subtle font-monospace fw-bold">
                                            #ORD-{{ sprintf('%04d', $order->id) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 32px; height: 32px; font-weight: 600; font-size: 0.85rem;">
                                                {{ strtoupper(substr($order->customer_name, 0, 1)) }}
                                            </div>
                                            <span class="fw-semibold text-dark">{{ $order->customer_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->whatsapp_number)) }}" target="_blank" class="text-decoration-none text-secondary small">
                                            <i class="fa-brands fa-whatsapp text-success me-1"></i> {{ $order->whatsapp_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">Rp {{ number_format($order->total_price, 0, ',', '.') }}</span>
                                    </td>
                                    <td>
                                        @if($order->status === 'Antrean')
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Antrean</span>
                                        @elseif($order->status === 'Dicuci')
                                            <span class="badge bg-info text-dark"><i class="fa-solid fa-soap me-1"></i> Dicuci</span>
                                        @elseif($order->status === 'Selesai')
                                            <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Selesai</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $order->status }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-secondary">
                                        {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : url('/admin/pesanan') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3" title="Kelola Pesanan">
                                            Kelola
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-inbox fs-1 mb-2 d-block text-secondary opacity-50"></i>
                                        <span class="fw-semibold">Belum ada transaksi pesanan yang tercatat.</span>
                                        <p class="small text-secondary mb-0">Pesanan baru yang dikirimkan pelanggan akan muncul di sini secara otomatis.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Shortcuts & Quick Actions -->
    <div class="col-lg-4">
        <!-- Quick Action Shortcuts -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-bolt text-warning me-2"></i> Akses Cepat
                </h5>
            </div>
            <div class="card-body p-4 pt-0 d-flex flex-column gap-3">
                <a href="{{ Route::has('admin.orders.index') ? route('admin.orders.index') : url('/admin/pesanan') }}" class="d-flex align-items-center p-3 rounded-3 border bg-light text-decoration-none hover-shadow-sm transition">
                    <div class="bg-primary text-white rounded-3 p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="d-block fw-bold text-dark">Kelola Status Pesanan</span>
                        <small class="text-muted">Perbarui status Antrean, Dicuci, & Selesai</small>
                    </div>
                    <i class="fa-solid fa-chevron-right text-secondary small"></i>
                </a>

                <a href="{{ Route::has('admin.services.index') ? route('admin.services.index') : url('/admin/layanan') }}" class="d-flex align-items-center p-3 rounded-3 border bg-light text-decoration-none hover-shadow-sm transition">
                    <div class="bg-success text-white rounded-3 p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="d-block fw-bold text-dark">Katalog Paket Layanan</span>
                        <small class="text-muted">Tambah, ubah harga, atau hapus paket cuci</small>
                    </div>
                    <i class="fa-solid fa-chevron-right text-secondary small"></i>
                </a>

                <a href="{{ url('/') }}" target="_blank" class="d-flex align-items-center p-3 rounded-3 border bg-light text-decoration-none hover-shadow-sm transition">
                    <div class="bg-info text-white rounded-3 p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                    <div class="flex-grow-1">
                        <span class="d-block fw-bold text-dark">Halaman Publik Laundry</span>
                        <small class="text-muted">Buka portal pemesanan dan pelacakan</small>
                    </div>
                    <i class="fa-solid fa-arrow-up-right-from-square text-secondary small"></i>
                </a>
            </div>
        </div>

        <!-- System Information Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-light">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-circle-info text-primary me-2"></i> Panduan Penggunaan
                </h6>
                <ul class="list-unstyled text-secondary small d-flex flex-column gap-2 mb-0">
                    <li><i class="fa-solid fa-check text-success me-2"></i> Periksa antrean pesanan masuk setiap awal shift.</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i> Ubah status cucian tepat waktu agar pelanggan dapat memantau status secara langsung melalui portal lacak.</li>
                    <li><i class="fa-solid fa-check text-success me-2"></i> Pastikan harga paket cuci di menu Layanan selalu mutakhir.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

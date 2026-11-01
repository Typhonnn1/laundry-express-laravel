@extends('layouts.app')

@section('title', 'Laundry Express - Cepat, Bersih, Wangi & Terpercaya')

@section('content')
    <!-- ======================================================== -->
    <!-- SECTION 1: HERO SECTION                                  -->
    <!-- ======================================================== -->
    <section id="hero" class="py-5 bg-white border-bottom position-relative overflow-hidden">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <!-- Hero Left: Headline, Value Proposition & Action Buttons -->
                <div class="col-lg-7">
                    <div class="d-inline-flex align-items-center gap-2 bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold small mb-3">
                        <i class="fa-solid fa-sparkles text-primary"></i>
                        <span>Layanan Cuci Kilat &amp; Berkualitas</span>
                    </div>

                    <h1 class="display-5 fw-bold text-dark lh-sm mb-3">
                        Laundry Express — <span class="text-primary">Cepat, Bersih, Wangi &amp; Terpercaya</span>
                    </h1>

                    <p class="lead text-secondary mb-4">
                        Solusi laundry modern untuk pakaian harian, seragam kerja, bed cover, dan busana formal. Dicuci terpisah, menggunakan deterjen ramah serat kain, disetrika uap rapi, dan dikemas higienis.
                    </p>

                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <a href="#form-pemesanan" class="btn btn-primary btn-lg rounded-pill px-4 py-3 shadow-sm fw-semibold">
                            <i class="fa-solid fa-basket-shopping me-2"></i> Pesan Sekarang
                        </a>
                        <a href="#lacak-pesanan" class="btn btn-outline-secondary btn-lg rounded-pill px-4 py-3 fw-semibold">
                            <i class="fa-solid fa-magnifying-glass me-2"></i> Lacak Pesanan
                        </a>
                    </div>

                    <!-- Trust Micro-Badges -->
                    <div class="row g-2 pt-2 text-secondary small fw-medium">
                        <div class="col-6 col-sm-3 d-flex align-items-center">
                            <i class="fa-solid fa-bolt text-danger me-2"></i> Express 3 Jam
                        </div>
                        <div class="col-6 col-sm-3 d-flex align-items-center">
                            <i class="fa-solid fa-shield-halved text-success me-2"></i> Garansi Higienis
                        </div>
                        <div class="col-6 col-sm-3 d-flex align-items-center">
                            <i class="fa-solid fa-soap text-primary me-2"></i> Wangi Tahan Lama
                        </div>
                        <div class="col-6 col-sm-3 d-flex align-items-center">
                            <i class="fa-solid fa-truck-fast text-info me-2"></i> Siap Antar-Jemput
                        </div>
                    </div>
                </div>

                <!-- Hero Right: Interactive Visual Card & Stats -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-lg rounded-4 p-4 bg-light position-relative">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                            <div class="d-flex align-items-center">
                                <span class="bg-primary text-white rounded-circle p-2 me-2 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-jug-detergent"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Keunggulan Layanan</h6>
                                    <span class="text-muted small">Standard Mutu Laundry Express</span>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                <i class="fa-solid fa-circle-check me-1"></i> Aktif
                            </span>
                        </div>

                        <div class="vstack gap-3">
                            <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 shadow-sm border border-light">
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-weight-scale text-primary fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Cuci Kiloan Hemat</div>
                                        <div class="text-muted small">Mulai Rp 5.000 / Kg</div>
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark border">Harian</span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 shadow-sm border border-light">
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-stopwatch text-danger fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Kilat Express 3 Jam</div>
                                        <div class="text-muted small">Selesai di hari yang sama</div>
                                    </div>
                                </div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Prioritas</span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 shadow-sm border border-light">
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-award text-warning fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Dry Cleaning Satuan</div>
                                        <div class="text-muted small">Jas, Blazer, &amp; Bed Cover</div>
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark border">Spesial</span>
                            </div>
                        </div>

                        <!-- Rating Strip -->
                        <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between text-muted small">
                            <span><i class="fa-solid fa-star text-warning me-1"></i><strong>4.9 / 5.0</strong> dari 10.000+ cucian</span>
                            <a href="#layanan" class="text-primary text-decoration-none fw-semibold">Lihat Paket <i class="fa-solid fa-arrow-down small ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 2: SERVICE CATALOG GRID SECTION                  -->
    <!-- ======================================================== -->
    <section id="layanan" class="py-5">
        <div class="container py-lg-4">
            <!-- Section Header -->
            <div class="text-center mb-5">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold small mb-2">
                    KATALOG PAKET CUCIAN
                </span>
                <h2 class="fw-bold text-dark mt-2 mb-3">Pilihan Layanan Laundry Unggulan</h2>
                <p class="text-secondary mx-auto" style="max-width: 650px;">
                    Pilih paket layanan yang sesuai dengan kebutuhan Anda. Dari cucian kiloan harian yang ekonomis hingga perawatan busana khusus satuan dan express.
                </p>
            </div>

            <!-- Service Cards Grid -->
            <div class="row g-4">
                @forelse($services as $service)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm hover-shadow-lg rounded-4 overflow-hidden position-relative">
                            <!-- Category Color Stripe Accent -->
                            <div class="py-1 {{ $service->category === 'Express' ? 'bg-danger' : ($service->category === 'Satuan' ? 'bg-success' : 'bg-primary') }}"></div>

                            <div class="card-body p-4 d-flex flex-column">
                                <!-- Category Badge & Icon -->
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    @if($service->category === 'Express')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2">
                                            <i class="fa-solid fa-bolt me-1"></i> Express
                                        </span>
                                        <span class="text-danger fs-5"><i class="fa-solid fa-clock-fast"></i></span>
                                    @elseif($service->category === 'Satuan')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                                            <i class="fa-solid fa-shirt me-1"></i> Satuan
                                        </span>
                                        <span class="text-success fs-5"><i class="fa-solid fa-vest"></i></span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">
                                            <i class="fa-solid fa-scale-balanced me-1"></i> Kiloan
                                        </span>
                                        <span class="text-primary fs-5"><i class="fa-solid fa-basket-shopping"></i></span>
                                    @endif
                                </div>

                                <!-- Service Name -->
                                <h4 class="card-title fw-bold text-dark mb-2">{{ $service->service_name }}</h4>

                                <!-- Service Description -->
                                <p class="card-text text-secondary small flex-grow-1 mb-4">
                                    {{ $service->description ?? 'Layanan pencucian profesional, higienis, dan terpercaya.' }}
                                </p>

                                <!-- Pricing & Unit (Matches strict E2E test assertions: '7.000', '/ Kg', '25.000', '/ Pcs') -->
                                <div class="bg-light p-3 rounded-3 mb-4 border border-light">
                                    <div class="text-muted small mb-1">Tarif Layanan:</div>
                                    <div class="d-flex align-items-baseline">
                                        <span class="fs-6 text-muted fw-semibold me-1">Rp</span>
                                        <span class="fs-3 fw-bold text-primary">{{ number_format($service->price_per_unit, 0, ',', '.') }}</span>
                                        <span class="fs-6 text-muted fw-normal ms-1">/ {{ $service->unit_type }}</span>
                                    </div>
                                </div>

                                <!-- Quick Select CTA -->
                                <button type="button" 
                                        class="btn btn-outline-primary w-100 rounded-pill py-2 fw-semibold select-service-btn btn-select-service"
                                        data-service-id="{{ $service->id }}"
                                        data-service-name="{{ $service->service_name }}"
                                        data-price="{{ $service->price_per_unit }}"
                                        data-unit="{{ $service->unit_type }}">
                                    <i class="fa-solid fa-cart-plus me-1"></i> Pilih Layanan
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                            <i class="fa-solid fa-box-open text-muted fs-1 mb-3"></i>
                            <h5 class="fw-bold text-dark">Belum Ada Paket Layanan</h5>
                            <p class="text-muted mb-0">Paket layanan laundry akan segera diperbarui oleh admin.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 3: ORDER FORM WITH DYNAMIC REPEATER              -->
    <!-- ======================================================== -->
    <section id="form-pemesanan" class="py-5 bg-white border-top border-bottom">
        <div class="container py-lg-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-header bg-primary text-white p-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-white text-primary rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="fa-solid fa-clipboard-list fs-4"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold text-white">Formulir Pesanan Laundry</h3>
                                    <p class="mb-0 text-white-50 small">Pilih layanan, tentukan jumlah atau perkiraan berat, dan total harga akan dihitung secara langsung.</p>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4 p-md-5">
                            <form action="{{ url('/pesan') }}" method="POST" id="order-form" novalidate>
                                @csrf

                                {{-- Section 1: Customer Details --}}
                                <div class="mb-4">
                                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                        <i class="fa-solid fa-user-circle text-primary me-2"></i>1. Data Pelanggan
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="customer_name" class="form-label fw-semibold">
                                                Nama Lengkap Pelanggan <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-user"></i></span>
                                                <input type="text" 
                                                       name="customer_name" 
                                                       id="customer_name" 
                                                       class="form-control @error('customer_name') is-invalid @enderror" 
                                                       placeholder="Masukkan nama lengkap Anda" 
                                                       value="{{ old('customer_name') }}" 
                                                       maxlength="100" 
                                                       required>
                                                @error('customer_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="whatsapp_number" class="form-label fw-semibold">
                                                Nomor WhatsApp Aktif <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light text-muted"><i class="fa-brands fa-whatsapp text-success"></i></span>
                                                <input type="tel" 
                                                       name="whatsapp_number" 
                                                       id="whatsapp_number" 
                                                       class="form-control @error('whatsapp_number') is-invalid @enderror" 
                                                       placeholder="Contoh: 081234567890 atau 6281234567890" 
                                                       value="{{ old('whatsapp_number') }}" 
                                                       maxlength="20" 
                                                       required>
                                                @error('whatsapp_number')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="form-text small text-muted">Nomor WhatsApp digunakan untuk konfirmasi nota digital dan pelacakan cucian.</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Section 2: Order Items Repeater --}}
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                        <h5 class="fw-bold text-dark mb-0">
                                            <i class="fa-solid fa-basket-shopping text-primary me-2"></i>2. Rincian Layanan Laundry
                                        </h5>
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-semibold shadow-sm" id="add-item-btn">
                                            <i class="fa-solid fa-plus-circle me-1"></i> Tambah Layanan
                                        </button>
                                    </div>

                                    @error('items')
                                        <div class="alert alert-danger py-2 small mb-3">{{ $message }}</div>
                                    @enderror

                                    {{-- Repeater Items Container --}}
                                    <div id="items-container">
                                        @php
                                            $oldItems = old('items');
                                        @endphp

                                        @if(!empty($oldItems) && is_array($oldItems))
                                            @foreach($oldItems as $idx => $oldItem)
                                                <div class="card mb-3 item-row border-light-subtle shadow-sm rounded-3">
                                                    <div class="card-body p-3">
                                                        <div class="row g-3 align-items-end">
                                                            {{-- Service Selection Dropdown --}}
                                                            <div class="col-md-5">
                                                                <label class="form-label fw-semibold small mb-1">
                                                                    <i class="fa-solid fa-tag text-primary me-1"></i>Pilih Paket Layanan <span class="text-danger">*</span>
                                                                </label>
                                                                <select name="items[{{ $idx }}][service_id]" class="form-select service-select @error("items.{$idx}.service_id") is-invalid @enderror" required>
                                                                    <option value="" data-price="0" data-unit="Kg" disabled {{ empty($oldItem['service_id']) ? 'selected' : '' }}>-- Pilih Paket Layanan --</option>
                                                                    @foreach($services as $service)
                                                                        <option value="{{ $service->id }}" 
                                                                                data-price="{{ $service->price_per_unit }}" 
                                                                                data-unit="{{ $service->unit_type }}"
                                                                                {{ (isset($oldItem['service_id']) && $oldItem['service_id'] == $service->id) ? 'selected' : '' }}>
                                                                            {{ $service->service_name }} ({{ $service->category }}) — Rp {{ number_format($service->price_per_unit, 0, ',', '.') }} / {{ $service->unit_type }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                @error("items.{$idx}.service_id")
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>

                                                            {{-- Quantity or Weight Input --}}
                                                            <div class="col-md-3">
                                                                <label class="form-label fw-semibold small mb-1">
                                                                    <i class="fa-solid fa-weight-hanging text-primary me-1"></i>Jumlah / Berat (<span class="unit-label">Kg</span>) <span class="text-danger">*</span>
                                                                </label>
                                                                <div class="input-group">
                                                                    <input type="number" 
                                                                           name="items[{{ $idx }}][qty_or_weight]" 
                                                                           class="form-control item-qty @error("items.{$idx}.qty_or_weight") is-invalid @enderror" 
                                                                           min="1" 
                                                                           value="{{ $oldItem['qty_or_weight'] ?? 1 }}" 
                                                                           required 
                                                                           placeholder="1">
                                                                    <span class="input-group-text unit-badge bg-light fw-bold text-muted">Kg</span>
                                                                    @error("items.{$idx}.qty_or_weight")
                                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                                    @enderror
                                                                </div>
                                                            </div>

                                                            {{-- Subtotal Display --}}
                                                            <div class="col-md-3">
                                                                <label class="form-label fw-semibold small mb-1 text-muted">Subtotal</label>
                                                                <div class="form-control-plaintext fw-bold fs-6 text-success row-subtotal">Rp 0</div>
                                                            </div>

                                                            {{-- Remove Row Action --}}
                                                            <div class="col-md-1 text-end">
                                                                <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" title="Hapus Layanan">
                                                                    <i class="fa-solid fa-trash-alt"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Default Initial Row (Index 0) --}}
                                            <div class="card mb-3 item-row border-light-subtle shadow-sm rounded-3">
                                                <div class="card-body p-3">
                                                    <div class="row g-3 align-items-end">
                                                        {{-- Service Selection Dropdown --}}
                                                        <div class="col-md-5">
                                                            <label class="form-label fw-semibold small mb-1">
                                                                <i class="fa-solid fa-tag text-primary me-1"></i>Pilih Paket Layanan <span class="text-danger">*</span>
                                                            </label>
                                                            <select name="items[0][service_id]" class="form-select service-select" required>
                                                                <option value="" data-price="0" data-unit="Kg" selected disabled>-- Pilih Paket Layanan --</option>
                                                                @foreach($services as $service)
                                                                    <option value="{{ $service->id }}" 
                                                                            data-price="{{ $service->price_per_unit }}" 
                                                                            data-unit="{{ $service->unit_type }}">
                                                                        {{ $service->service_name }} ({{ $service->category }}) — Rp {{ number_format($service->price_per_unit, 0, ',', '.') }} / {{ $service->unit_type }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        {{-- Quantity or Weight Input --}}
                                                        <div class="col-md-3">
                                                            <label class="form-label fw-semibold small mb-1">
                                                                <i class="fa-solid fa-weight-hanging text-primary me-1"></i>Jumlah / Berat (<span class="unit-label">Kg</span>) <span class="text-danger">*</span>
                                                            </label>
                                                            <div class="input-group">
                                                                <input type="number" 
                                                                       name="items[0][qty_or_weight]" 
                                                                       class="form-control item-qty" 
                                                                       min="1" 
                                                                       value="1" 
                                                                       required 
                                                                       placeholder="1">
                                                                <span class="input-group-text unit-badge bg-light fw-bold text-muted">Kg</span>
                                                            </div>
                                                        </div>

                                                        {{-- Subtotal Display --}}
                                                        <div class="col-md-3">
                                                            <label class="form-label fw-semibold small mb-1 text-muted">Subtotal</label>
                                                            <div class="form-control-plaintext fw-bold fs-6 text-success row-subtotal">Rp 0</div>
                                                        </div>

                                                        {{-- Remove Row Action --}}
                                                        <div class="col-md-1 text-end">
                                                            <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" title="Hapus Layanan">
                                                                <i class="fa-solid fa-trash-alt"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Section 3: Summary and Submission Card --}}
                                <div class="card bg-light border-0 rounded-3 shadow-sm mt-4">
                                    <div class="card-body p-4">
                                        <div class="row align-items-center">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <div class="d-flex align-items-center mb-1">
                                                    <i class="fa-solid fa-info-circle text-primary me-2"></i>
                                                    <span class="text-muted fw-semibold">Jumlah Layanan Dipilih:</span>
                                                    <span id="total-items-count" class="badge bg-primary ms-2 fs-6">1 Layanan</span>
                                                </div>
                                                <small class="text-muted fst-italic">Perhitungan subtotal & grand total dihitung live tanpa reload halaman. Harga final diverifikasi resmi oleh server.</small>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <div class="text-muted small text-uppercase fw-bold letter-spacing-1">Total Estimasi Biaya</div>
                                                <div id="grand-total-display" class="fw-bolder fs-2 text-primary">Rp 0</div>
                                            </div>
                                        </div>

                                        <div class="border-top mt-3 pt-3">
                                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm" id="submit-order-btn">
                                                <i class="fa-solid fa-paper-plane me-2"></i>Kirim Pesanan Laundry Sekarang
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================== -->
    <!-- SECTION 4: ORDER TRACKING SEARCH & PROGRESS BAR          -->
    <!-- ======================================================== -->
    <section id="lacak-pesanan" class="py-5 bg-light">
        <div class="container py-lg-4">
            <div class="row justify-content-center text-center mb-4">
                <div class="col-lg-7">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-2">
                        <i class="fa-solid fa-satellite-dish me-1"></i> Pelacakan Real-Time
                    </span>
                    <h2 class="fw-bold text-dark">Lacak Status Pesanan Laundry</h2>
                    <p class="text-muted">
                        Masukkan nomor WhatsApp atau ID Pesanan Anda untuk memantau proses pencucian pakaian secara transparan dan akurat.
                    </p>
                </div>
            </div>

            <!-- Search Input Bar -->
            <div class="row justify-content-center mb-4">
                <div class="col-lg-7">
                    <form action="{{ url('/') }}#lacak-pesanan" method="GET" class="card shadow-sm border-0 p-2">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white border-0 text-muted ps-3">
                                <i class="fa-solid fa-search"></i>
                            </span>
                            <input type="text" 
                                   name="search" 
                                   class="form-control border-0 ps-2" 
                                   placeholder="Cari berdasarkan No. WhatsApp (08xxx) atau ID Pesanan..." 
                                   value="{{ $search ?? '' }}" 
                                   required>
                            <button class="btn btn-primary px-4 fw-semibold rounded-3" type="submit">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search Result: Not Found Feedback -->
            @if(isset($searchPerformed) && $searchPerformed && (!isset($trackedOrders) || $trackedOrders->isEmpty()))
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="alert alert-warning border-0 shadow-sm text-center py-4 px-3 rounded-3">
                            <i class="fa-solid fa-triangle-exclamation text-warning fs-1 mb-2 d-block"></i>
                            <h5 class="fw-bold text-dark mb-1">Pesanan tidak ditemukan</h5>
                            <p class="text-muted mb-0">
                                Pesanan dengan nomor WhatsApp atau ID "<strong>{{ e($search) }}</strong>" tidak ditemukan dalam sistem kami.
                                Silakan periksa kembali nomor yang Anda masukkan atau hubungi staf kami.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Search Result: Orders Found -->
            @if(isset($trackedOrders) && $trackedOrders->isNotEmpty())
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        @foreach($trackedOrders as $order)
                            <div class="card shadow-sm border-0 mb-4 overflow-hidden rounded-3">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="text-muted small">Nomor Pesanan</span>
                                        <h5 class="fw-bold mb-0 text-dark">
                                            #LE-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} 
                                            <small class="text-muted fw-normal">(#{{ $order->id }})</small>
                                        </h5>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-{{ $order->status_badge_class }} fs-6 px-3 py-2 rounded-pill shadow-sm">
                                            <i class="fa-solid @if($order->status == 'Antrean') fa-clock @elseif($order->status == 'Dicuci') fa-jug-detergent @else fa-circle-check @endif me-1"></i>
                                            {{ $order->status }}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body p-4">
                                    <div class="row mb-4">
                                        <div class="col-sm-6 mb-2 mb-sm-0">
                                            <span class="text-muted small d-block">Nama Pelanggan</span>
                                            <strong class="fs-6 text-dark">{{ $order->customer_name }}</strong>
                                            <div class="text-muted small">{{ $order->whatsapp_number }}</div>
                                        </div>
                                        <div class="col-sm-6 text-sm-end">
                                            <span class="text-muted small d-block">Total Tagihan</span>
                                            <strong class="fs-5 text-primary">{{ $order->formatted_total_price }}</strong>
                                            <div class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }} WIB</div>
                                        </div>
                                    </div>

                                    <!-- 3-Stage Animated Progress Bar -->
                                    <div class="tracking-progress-wrapper mb-4">
                                        <div class="progress position-relative" style="height: 12px; border-radius: 6px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                                 role="progressbar" 
                                                 style="width: {{ $order->progress_percentage }}%;" 
                                                 aria-valuenow="{{ $order->progress_percentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-3 text-center">
                                            <!-- Stage 1: Antrean (33%) -->
                                            <div class="tracking-stage flex-fill">
                                                <div class="stage-icon mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center {{ in_array($order->status, ['Antrean', 'Dicuci', 'Selesai']) ? 'bg-primary text-white shadow-sm' : 'bg-light text-muted' }}" style="width: 42px; height: 42px;">
                                                    <i class="fa-solid fa-clock"></i>
                                                </div>
                                                <div class="fw-bold small text-dark">Antrean</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Masuk antrean</div>
                                            </div>

                                            <!-- Stage 2: Dicuci (66%) -->
                                            <div class="tracking-stage flex-fill">
                                                <div class="stage-icon mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center {{ in_array($order->status, ['Dicuci', 'Selesai']) ? 'bg-primary text-white shadow-sm' : 'bg-light text-muted' }}" style="width: 42px; height: 42px;">
                                                    <i class="fa-solid fa-jug-detergent"></i>
                                                </div>
                                                <div class="fw-bold small text-dark">Dicuci</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Pencucian &amp; setrika</div>
                                            </div>

                                            <!-- Stage 3: Selesai (100%) -->
                                            <div class="tracking-stage flex-fill">
                                                <div class="stage-icon mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center {{ $order->status === 'Selesai' ? 'bg-success text-white shadow-sm' : 'bg-light text-muted' }}" style="width: 42px; height: 42px;">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </div>
                                                <div class="fw-bold small text-dark">Selesai</div>
                                                <div class="text-muted" style="font-size: 0.75rem;">Siap diambil</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Narrative Alert -->
                                    <div class="alert @if($order->status === 'Antrean') alert-warning @elseif($order->status === 'Dicuci') alert-info @else alert-success @endif mb-3 py-2 px-3 small d-flex align-items-center rounded-3">
                                        <i class="fa-solid @if($order->status === 'Antrean') fa-info-circle @elseif($order->status === 'Dicuci') fa-spinner fa-spin @else fa-check-circle @endif me-2 fs-5"></i>
                                        <span>
                                            @if($order->status === 'Antrean')
                                                Pesanan Anda telah tercatat dan sedang berada dalam antrean penanganan outlet.
                                            @elseif($order->status === 'Dicuci')
                                                Pakaian Anda sedang dalam proses pencucian, pengeringan, dan penyetrikaan higienis.
                                            @else
                                                Cucian Anda telah selesai! Silakan mengambil pakaian di outlet kami atau hubungi kami untuk pengantaran.
                                            @endif
                                        </span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-flex justify-content-end gap-2 pt-2">
                                        <a href="{{ url('/nota/' . $order->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fa-solid fa-file-invoice me-1"></i> Rincian Nota
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Reusable Item Row HTML Template for Vanilla JS Cloning --}}
    <template id="item-row-template">
        <div class="card mb-3 item-row border-light-subtle shadow-sm rounded-3">
            <div class="card-body p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa-solid fa-tag text-primary me-1"></i>Pilih Paket Layanan <span class="text-danger">*</span>
                        </label>
                        <select name="items[__INDEX__][service_id]" class="form-select service-select" required>
                            <option value="" data-price="0" data-unit="Kg" selected disabled>-- Pilih Paket Layanan --</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" 
                                        data-price="{{ $service->price_per_unit }}" 
                                        data-unit="{{ $service->unit_type }}">
                                    {{ $service->service_name }} ({{ $service->category }}) — Rp {{ number_format($service->price_per_unit, 0, ',', '.') }} / {{ $service->unit_type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa-solid fa-weight-hanging text-primary me-1"></i>Jumlah / Berat (<span class="unit-label">Kg</span>) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   name="items[__INDEX__][qty_or_weight]" 
                                   class="form-control item-qty" 
                                   min="1" 
                                   value="1" 
                                   required 
                                   placeholder="1">
                            <span class="input-group-text unit-badge bg-light fw-bold text-muted">Kg</span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1 text-muted">Subtotal</label>
                        <div class="form-control-plaintext fw-bold fs-6 text-success row-subtotal">Rp 0</div>
                    </div>

                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" title="Hapus Layanan">
                            <i class="fa-solid fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsContainer = document.getElementById('items-container');
    const template = document.getElementById('item-row-template');
    const addItemBtn = document.getElementById('add-item-btn');
    const grandTotalDisplay = document.getElementById('grand-total-display');
    const totalItemsCount = document.getElementById('total-items-count');
    const orderForm = document.getElementById('order-form');

    if (!itemsContainer) return;

    let rowIndexCounter = document.querySelectorAll('#items-container .item-row').length;

    /**
     * Format integer number into Indonesian Rupiah format (e.g. 14000 -> Rp 14.000).
     */
    function formatRupiah(amount) {
        const num = Math.max(0, parseInt(amount, 10) || 0);
        return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    /**
     * Recalculate subtotal for a specific item row.
     */
    function updateRowSubtotal(row) {
        if (!row) return 0;

        const select = row.querySelector('.service-select');
        const qtyInput = row.querySelector('.item-qty');
        const unitLabel = row.querySelector('.unit-label');
        const unitBadge = row.querySelector('.unit-badge');
        const subtotalElem = row.querySelector('.row-subtotal');

        let price = 0;
        let unit = 'Kg';

        if (select && select.selectedIndex >= 0) {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.value) {
                price = parseInt(selectedOption.getAttribute('data-price'), 10) || 0;
                unit = selectedOption.getAttribute('data-unit') || 'Kg';
            }
        }

        if (unitLabel) unitLabel.textContent = unit;
        if (unitBadge) unitBadge.textContent = unit;

        let qty = 0;
        if (qtyInput) {
            qty = parseInt(qtyInput.value, 10);
            if (isNaN(qty) || qty < 1) {
                qty = 0;
            }
        }

        const subtotal = price * qty;
        if (subtotalElem) {
            subtotalElem.textContent = formatRupiah(subtotal);
        }

        return subtotal;
    }

    /**
     * Recalculate grand total across all rows in the container.
     */
    function updateGrandTotal() {
        const rows = itemsContainer.querySelectorAll('.item-row');
        let grandTotal = 0;
        let validRowsCount = 0;

        rows.forEach(function (row) {
            const select = row.querySelector('.service-select');
            const qtyInput = row.querySelector('.item-qty');

            if (select && select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const price = parseInt(selectedOption.getAttribute('data-price'), 10) || 0;
                const qty = parseInt(qtyInput ? qtyInput.value : 0, 10) || 0;
                grandTotal += (price * Math.max(0, qty));
                validRowsCount++;
            }
        });

        if (grandTotalDisplay) {
            grandTotalDisplay.textContent = formatRupiah(grandTotal);
        }

        if (totalItemsCount) {
            totalItemsCount.textContent = (validRowsCount > 0 ? validRowsCount : rows.length) + ' Layanan';
        }
    }

    /**
     * Enable or disable remove buttons to enforce minimum 1 row boundary constraint.
     */
    function updateDeleteButtonsState() {
        const rows = itemsContainer.querySelectorAll('.item-row');
        const removeButtons = itemsContainer.querySelectorAll('.remove-item-btn');

        if (rows.length <= 1) {
            removeButtons.forEach(function (btn) {
                btn.disabled = true;
                btn.setAttribute('title', 'Pesanan harus memiliki minimal 1 layanan laundry');
            });
        } else {
            removeButtons.forEach(function (btn) {
                btn.disabled = false;
                btn.setAttribute('title', 'Hapus layanan ini');
            });
        }
    }

    /**
     * Add a new item row to the repeater container.
     */
    function addNewRow(selectedServiceId = null, initialQty = 1) {
        if (!template) return;

        const newIndex = rowIndexCounter++;
        const rowHtml = template.innerHTML.replaceAll('__INDEX__', newIndex);

        const wrapper = document.createElement('div');
        wrapper.innerHTML = rowHtml.trim();
        const newRow = wrapper.firstChild;

        itemsContainer.appendChild(newRow);

        if (selectedServiceId) {
            const select = newRow.querySelector('.service-select');
            if (select) {
                select.value = selectedServiceId;
            }
        }

        if (initialQty) {
            const qtyInput = newRow.querySelector('.item-qty');
            if (qtyInput) {
                qtyInput.value = initialQty;
            }
        }

        updateRowSubtotal(newRow);
        updateGrandTotal();
        updateDeleteButtonsState();

        const newSelect = newRow.querySelector('.service-select');
        if (newSelect && !selectedServiceId) {
            newSelect.focus();
        }
    }

    // Event delegation on items container
    itemsContainer.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('service-select')) {
            const row = e.target.closest('.item-row');
            updateRowSubtotal(row);
            updateGrandTotal();
        }
    });

    itemsContainer.addEventListener('input', function (e) {
        if (e.target && e.target.classList.contains('item-qty')) {
            const row = e.target.closest('.item-row');
            updateRowSubtotal(row);
            updateGrandTotal();
        }
    });

    itemsContainer.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-item-btn');
        if (!removeBtn) return;

        const rows = itemsContainer.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            return;
        }

        const row = removeBtn.closest('.item-row');
        if (row) {
            row.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.97)';
            setTimeout(function () {
                row.remove();
                updateGrandTotal();
                updateDeleteButtonsState();
            }, 150);
        }
    });

    if (addItemBtn) {
        addItemBtn.addEventListener('click', function () {
            addNewRow();
        });
    }

    // Quick select from catalog cards
    document.querySelectorAll('.select-service-btn, .btn-select-service').forEach(function (button) {
        button.addEventListener('click', function () {
            const serviceId = this.getAttribute('data-service-id');
            if (!serviceId) return;

            const orderSection = document.getElementById('form-pemesanan');
            if (orderSection) {
                orderSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            const rows = itemsContainer.querySelectorAll('.item-row');
            let targetRow = null;

            for (const r of rows) {
                const sel = r.querySelector('.service-select');
                if (sel && (!sel.value || sel.value === '')) {
                    targetRow = r;
                    break;
                }
            }

            if (targetRow) {
                const sel = targetRow.querySelector('.service-select');
                sel.value = serviceId;
                updateRowSubtotal(targetRow);
                updateGrandTotal();
            } else {
                addNewRow(serviceId, 1);
            }
        });
    });

    // Form submit client-side validation guard
    if (orderForm) {
        orderForm.addEventListener('submit', function (e) {
            const rows = itemsContainer.querySelectorAll('.item-row');
            let hasValidSelection = false;

            rows.forEach(function (row) {
                const sel = row.querySelector('.service-select');
                const qty = row.querySelector('.item-qty');
                if (sel && sel.value) {
                    hasValidSelection = true;
                }
                if (qty && (parseInt(qty.value, 10) < 1 || isNaN(parseInt(qty.value, 10)))) {
                    qty.value = 1;
                }
            });

            if (!hasValidSelection) {
                e.preventDefault();
                alert('Silakan pilih minimal satu paket layanan laundry sebelum mengirim pesanan.');
                const firstSelect = itemsContainer.querySelector('.service-select');
                if (firstSelect) firstSelect.focus();
                return false;
            }

            const submitBtn = document.getElementById('submit-order-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses Pesanan...';
            }
        });
    }

    // Initial run on load
    const initialRows = itemsContainer.querySelectorAll('.item-row');
    initialRows.forEach(function (row) {
        updateRowSubtotal(row);
    });
    updateGrandTotal();
    updateDeleteButtonsState();
});
</script>
@endpush

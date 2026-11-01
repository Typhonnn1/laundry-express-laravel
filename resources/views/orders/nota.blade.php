@extends('layouts.app')

@section('title', 'Nota Pesanan #' . $order->id . ' - Laundry Express')

@push('styles')
<style>
    @media print {
        /* Hide all interface chrome and action controls */
        nav, footer, .d-print-none, .btn {
            display: none !important;
        }
        body {
            background-color: #fff !important;
            color: #000 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .container {
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            border: 1px solid #dee2e6 !important;
            box-shadow: none !important;
        }
        .card-header, .card-footer {
            background: #fff !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
        .table {
            border-color: #dee2e6 !important;
        }
        .badge {
            border: 1px solid #000 !important;
            color: #000 !important;
            background: transparent !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container py-4">
    @php
        $cleanPhone = preg_replace('/[^0-9]/', '', $order->whatsapp_number);
        $waPhone = str_starts_with($cleanPhone, '0') ? '62' . substr($cleanPhone, 1) : $cleanPhone;

        $waSummary = "*NOTA PESANAN - LAUNDRY EXPRESS*\n"
            . "No. Nota: #" . $order->id . "\n"
            . "Pelanggan: " . $order->customer_name . "\n"
            . "WhatsApp: " . $order->whatsapp_number . "\n"
            . "Tanggal: " . $order->created_at->format('d/m/Y H:i') . "\n"
            . "Status: " . $order->status . "\n"
            . "----------------------------------------\n"
            . "*Rincian Layanan:*\n";

        foreach ($order->orderDetails as $detail) {
            $waSummary .= "- " . $detail->service->service_name . " (" . $detail->qty_or_weight . " " . $detail->service->unit_type . ") : " . $detail->formatted_subtotal . "\n";
        }

        $waSummary .= "----------------------------------------\n"
            . "*Total Tagihan: " . $order->formatted_total_price . "*\n\n"
            . "Pantau progres cucian Anda di:\n"
            . url('/?search=' . $order->id) . "\n\n"
            . "Terima kasih telah mempercayakan pakaian Anda kepada Laundry Express!";

        $waUrl = "https://wa.me/" . $waPhone . "?text=" . rawurlencode($waSummary);
    @endphp

    <!-- Breadcrumb / Back Navigation (Hidden during print) -->
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Beranda
        </a>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary shadow-sm">
                <i class="fa-solid fa-print me-2"></i> Cetak Struk
            </button>
            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-success shadow-sm">
                <i class="fa-brands fa-whatsapp me-2"></i> Kirim WhatsApp
            </a>
        </div>
    </div>

    <!-- Printable Invoice Card -->
    <div class="card shadow-sm border-0" id="printable-receipt">
        <!-- Invoice Header -->
        <div class="card-header bg-white border-bottom p-4">
            <div class="row align-items-center">
                <div class="col-sm-7 mb-3 mb-sm-0">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fa-solid fa-shirt text-primary fs-2 me-2"></i>
                        <h3 class="fw-bold text-primary mb-0">Laundry Express</h3>
                    </div>
                    <p class="text-muted small mb-0">
                        Layanan Cuci Kilat, Bersih, Wangi &amp; Terpercaya<br>
                        Jl. Express Raya No. 12, Telp/WA: 0812-3456-7890
                    </p>
                </div>
                <div class="col-sm-5 text-sm-end">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold mb-2">
                        NOTA RESMI
                    </span>
                    <h5 class="fw-bold mb-1">Nota #LE-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</h5>
                    <div class="text-muted small">ID Pesanan: #{{ $order->id }}</div>
                    <div class="text-muted small">{{ $order->created_at->format('d/m/Y H:i') }} WIB</div>
                </div>
            </div>
        </div>

        <!-- Customer & Order Meta -->
        <div class="card-body p-4">
            <div class="row g-3 mb-4 p-3 bg-light rounded-3">
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted small d-block">Nama Pelanggan</span>
                    <strong class="fs-6 text-dark">{{ $order->customer_name }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted small d-block">Nomor WhatsApp</span>
                    <strong class="fs-6 text-dark">{{ $order->whatsapp_number }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted small d-block">Tanggal Masuk</span>
                    <strong class="fs-6 text-dark">{{ $order->created_at->format('d M Y') }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted small d-block">Status Pesanan</span>
                    <span class="badge bg-{{ $order->status_badge_class }} px-2 py-1">
                        {{ $order->status }}
                    </span>
                </div>
            </div>

            <!-- Itemized Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Paket Layanan</th>
                            <th class="text-center" style="width: 120px;">Kategori</th>
                            <th class="text-end" style="width: 150px;">Harga Satuan</th>
                            <th class="text-center" style="width: 130px;">Jumlah / Berat</th>
                            <th class="text-end" style="width: 160px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->orderDetails as $index => $detail)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $detail->service->service_name }}</div>
                                    @if($detail->service->description)
                                        <small class="text-muted">{{ $detail->service->description }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        {{ $detail->service->category }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($detail->service->price_per_unit, 0, ',', '.') }} / {{ $detail->service->unit_type }}
                                </td>
                                <td class="text-center fw-semibold">
                                    {{ $detail->qty_or_weight }} {{ $detail->service->unit_type }}
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    {{ $detail->formatted_subtotal }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Tidak ada rincian item layanan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider">
                        <tr>
                            <td colspan="5" class="text-end fw-bold fs-5 py-3">Total Pembayaran :</td>
                            <td class="text-end fw-bold text-primary fs-4 py-3">
                                {{ $order->formatted_total_price }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notice & Instructions -->
            <div class="row align-items-center pt-2">
                <div class="col-md-8 text-muted small">
                    <h6 class="fw-bold text-dark mb-1"><i class="fa-solid fa-circle-info me-1"></i> Ketentuan Pengambilan:</h6>
                    <ul class="mb-0 ps-3">
                        <li>Pengambilan cucian wajib menunjukkan struk ini atau mengonfirmasi nomor WhatsApp terdaftar.</li>
                        <li>Barang yang tidak diambil dalam tempo 30 hari di luar tanggung jawab laundry.</li>
                        <li>Periksa kembali pakaian saat pengambilan. Komplain maksimal 1x24 jam setelah serah terima.</li>
                    </ul>
                </div>
                <div class="col-md-4 text-center mt-3 mt-md-0">
                    <p class="small text-muted mb-4">Hormat Kami,</p>
                    <div class="fw-bold text-dark pt-3 border-top d-inline-block px-4">Laundry Express</div>
                </div>
            </div>
        </div>

        <!-- Footer Actions (Hidden during print) -->
        <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center d-print-none">
            <span class="text-muted small">
                <i class="fa-solid fa-shield-halved text-success me-1"></i> Transaksi tervalidasi secara sistematis.
            </span>
            <div class="d-flex gap-2">
                <button type="button" onclick="window.print()" class="btn btn-outline-primary">
                    <i class="fa-solid fa-print me-1"></i> Cetak
                </button>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-success">
                    <i class="fa-brands fa-whatsapp me-1"></i> Kirim ke WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

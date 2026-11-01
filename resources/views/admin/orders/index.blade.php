@extends('layouts.admin')

@section('title', 'Manajemen Pesanan - Admin Laundry Express')
@section('header_title', 'Manajemen Pesanan')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-list-check text-primary me-2"></i>Daftar Pesanan Masuk
            </h4>
            <p class="text-secondary small mb-0">Pantau seluruh alur pesanan cucian pelanggan dan perbarui status pengerjaan.</p>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <!-- Status Tabs -->
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.orders.index', array_filter(['search' => $search])) }}" 
                           class="btn btn-sm rounded-pill px-3 fw-semibold {{ empty($status) ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Semua <span class="badge {{ empty($status) ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ $statusCounts['all'] }}</span>
                        </a>
                        <a href="{{ route('admin.orders.index', array_filter(['status' => 'Antrean', 'search' => $search])) }}" 
                           class="btn btn-sm rounded-pill px-3 fw-semibold {{ $status === 'Antrean' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
                            <i class="fa-solid fa-clock me-1"></i> Antrean 
                            <span class="badge {{ $status === 'Antrean' ? 'bg-dark text-white' : 'bg-warning text-dark' }} ms-1">{{ $statusCounts['Antrean'] }}</span>
                        </a>
                        <a href="{{ route('admin.orders.index', array_filter(['status' => 'Dicuci', 'search' => $search])) }}" 
                           class="btn btn-sm rounded-pill px-3 fw-semibold {{ $status === 'Dicuci' ? 'btn-info text-dark' : 'btn-outline-info' }}">
                            <i class="fa-solid fa-soap me-1"></i> Dicuci 
                            <span class="badge {{ $status === 'Dicuci' ? 'bg-dark text-white' : 'bg-info text-dark' }} ms-1">{{ $statusCounts['Dicuci'] }}</span>
                        </a>
                        <a href="{{ route('admin.orders.index', array_filter(['status' => 'Selesai', 'search' => $search])) }}" 
                           class="btn btn-sm rounded-pill px-3 fw-semibold {{ $status === 'Selesai' ? 'btn-success' : 'btn-outline-success' }}">
                            <i class="fa-solid fa-circle-check me-1"></i> Selesai 
                            <span class="badge {{ $status === 'Selesai' ? 'bg-white text-success' : 'bg-success' }} ms-1">{{ $statusCounts['Selesai'] }}</span>
                        </a>
                    </div>
                </div>

                <!-- Search Input Form -->
                <div class="col-lg-4">
                    <form action="{{ route('admin.orders.index') }}" method="GET">
                        @if($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control rounded-start-pill ps-3" name="search" value="{{ $search }}" placeholder="Cari nama, WhatsApp, ID...">
                            <button class="btn btn-primary rounded-end-pill px-3" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                            @if(!empty($search) || !empty($status))
                                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary rounded-pill ms-1" title="Reset Filter">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3" style="width: 100px;">ID Order</th>
                            <th class="py-3">Tanggal</th>
                            <th class="py-3">Pelanggan</th>
                            <th class="py-3">Layanan</th>
                            <th class="py-3">Total Harga</th>
                            <th class="py-3" style="width: 200px;">Status Pesanan</th>
                            <th class="py-3 text-end pe-4" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-light text-primary border border-primary-subtle font-monospace fw-bold">
                                        #{{ $order->id }}
                                    </span>
                                </td>
                                <td class="text-secondary small">
                                    {{ $order->created_at->format('d/m/Y') }}<br>
                                    <span class="text-muted">{{ $order->created_at->format('H:i') }} WIB</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $order->customer_name }}</div>
                                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->whatsapp_number)) }}" 
                                       target="_blank" class="text-decoration-none text-success small">
                                        <i class="fa-brands fa-whatsapp me-1"></i>{{ $order->whatsapp_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="small">
                                        @foreach($order->orderDetails as $detail)
                                            <div>
                                                <span class="fw-semibold">{{ $detail->service->service_name ?? 'Layanan' }}</span>
                                                <span class="text-muted">({{ $detail->qty_or_weight }} {{ $detail->service->unit_type ?? 'Kg' }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary fs-6">
                                        Rp {{ number_format($order->total_price, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    <!-- Status Transition Form -->
                                    <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="input-group input-group-sm">
                                            <select name="status" class="form-select form-select-sm rounded-pill fw-semibold {{ $order->status === 'Antrean' ? 'text-warning border-warning' : ($order->status === 'Dicuci' ? 'text-info border-info' : 'text-success border-success') }}" onchange="this.form.submit()">
                                                <option value="Antrean" {{ $order->status === 'Antrean' ? 'selected' : '' }}>Antrean</option>
                                                <option value="Dicuci" {{ $order->status === 'Dicuci' ? 'selected' : '' }}>Dicuci</option>
                                                <option value="Selesai" {{ $order->status === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                            </select>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#orderDetailModal{{ $order->id }}" title="Lihat Rincian">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <a href="{{ route('order.show', $order->id) }}" target="_blank" class="btn btn-outline-secondary" title="Cetak Nota">
                                            <i class="fa-solid fa-receipt"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal{{ $order->id }}" title="Hapus Pesanan">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="fa-solid fa-clipboard-question fs-1 text-muted d-block mb-3"></i>
                                    <p class="mb-0">Tidak ada data pesanan yang sesuai dengan filter atau kata kunci pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($orders->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="d-flex justify-content-center">
                    {{ $orders->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modals Detail & Delete (Iterated per order) -->
@foreach($orders as $order)
    <!-- Modal Detail Order -->
    <div class="modal fade" id="orderDetailModal{{ $order->id }}" tabindex="-1" aria-labelledby="orderDetailModalLabel{{ $order->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="orderDetailModalLabel{{ $order->id }}">
                        <i class="fa-solid fa-receipt text-primary me-2"></i>Rincian Pesanan #{{ $order->id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded-3">
                                <span class="text-muted small d-block">Informasi Pelanggan</span>
                                <h6 class="fw-bold mb-1">{{ $order->customer_name }}</h6>
                                <div class="text-success small"><i class="fa-brands fa-whatsapp me-1"></i>{{ $order->whatsapp_number }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded-3">
                                <span class="text-muted small d-block">Status & Waktu Masuk</span>
                                <span class="badge bg-{{ $order->status === 'Antrean' ? 'warning text-dark' : ($order->status === 'Dicuci' ? 'info text-dark' : 'success') }} mb-1">{{ $order->status }}</span>
                                <div class="text-secondary small">{{ $order->created_at->format('d F Y, H:i') }} WIB</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Item Cucian</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Layanan</th>
                                    <th class="text-center">Kategori</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-center">Jumlah / Berat</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->orderDetails as $detail)
                                    <tr>
                                        <td class="fw-semibold">{{ $detail->service->service_name ?? '-' }}</td>
                                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $detail->service->category ?? '-' }}</span></td>
                                        <td class="text-end">Rp {{ number_format($detail->service->price_per_unit ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $detail->qty_or_weight }} {{ $detail->service->unit_type ?? '' }}</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="4" class="text-end">Total Pembayaran:</th>
                                    <th class="text-end fw-bold text-primary fs-6">
                                        Rp {{ number_format($order->total_price, 0, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <a href="{{ route('order.show', $order->id) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="fa-solid fa-print me-1"></i> Buka Nota Pembayaran
                    </a>
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Order -->
    <div class="modal fade" id="deleteOrderModal{{ $order->id }}" tabindex="-1" aria-labelledby="deleteOrderModalLabel{{ $order->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-danger" id="deleteOrderModalLabel{{ $order->id }}">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>Konfirmasi Hapus Pesanan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <p class="mb-1">Apakah Anda yakin ingin menghapus pesanan <strong>#{{ $order->id }}</strong> atas nama <strong>{{ $order->customer_name }}</strong>?</p>
                        <p class="text-danger small mb-0"><strong>Peringatan:</strong> Seluruh rincian item cucian akan dihapus secara permanen dan total pendapatan akan disesuaikan.</p>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Ya, Hapus Pesanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

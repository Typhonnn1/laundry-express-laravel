@extends('layouts.admin')

@section('title', 'Manajemen Layanan - Admin Laundry Express')
@section('header_title', 'Katalog Paket Layanan')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-tags text-primary me-2"></i>Daftar Paket Layanan
            </h4>
            <p class="text-secondary small mb-0">Kelola master harga, kategori, dan tarif layanan laundry express.</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createServiceModal">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Layanan</span>
        </button>
    </div>

    <!-- Services Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3">Nama Layanan</th>
                            <th class="py-3">Kategori</th>
                            <th class="py-3">Harga Satuan</th>
                            <th class="py-3">Unit</th>
                            <th class="py-3">Deskripsi</th>
                            <th class="py-3 text-end pe-4" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr>
                                <td class="ps-4 fw-semibold text-secondary">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold text-dark fs-6">{{ $service->service_name }}</div>
                                    <small class="text-muted d-md-none">{{ $service->unit_type }} - {{ $service->category }}</small>
                                </td>
                                <td>
                                    @if($service->category === 'Kiloan')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill">Kiloan</span>
                                    @elseif($service->category === 'Satuan')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 rounded-pill">Satuan</span>
                                    @elseif($service->category === 'Express')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">Express</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill">{{ $service->category }}</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-primary">
                                    Rp {{ number_format($service->price_per_unit, 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $service->unit_type }}</span>
                                </td>
                                <td class="text-secondary small">
                                    {{ $service->description ?? '-' }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}" title="Edit Layanan">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteServiceModal{{ $service->id }}" title="Hapus Layanan">
                                            <i class="fa-solid fa-trash me-1"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-secondary">
                                    <i class="fa-solid fa-box-open fs-1 text-muted d-block mb-3"></i>
                                    <p class="mb-2">Belum ada paket layanan laundry yang terdaftar.</p>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                                        <i class="fa-solid fa-plus me-1"></i> Tambah Layanan Sekarang
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create Service -->
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('admin.services.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="createServiceModalLabel">
                        <i class="fa-solid fa-plus-circle text-primary me-2"></i>Tambah Paket Layanan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label for="create_service_name" class="form-label fw-semibold">Nama Layanan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_service_name" name="service_name" placeholder="Contoh: Cuci Komplit Reguler" required maxlength="100">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="create_category" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="create_category" name="category" required>
                                <option value="" disabled selected>Pilih Kategori</option>
                                <option value="Kiloan">Kiloan</option>
                                <option value="Satuan">Satuan</option>
                                <option value="Express">Express</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="create_unit_type" class="form-label fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                            <select class="form-select" id="create_unit_type" name="unit_type" required>
                                <option value="" disabled selected>Pilih Unit</option>
                                <option value="Kg">Kg</option>
                                <option value="Pcs">Pcs</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_price_per_unit" class="form-label fw-semibold">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                            <input type="number" class="form-control" id="create_price_per_unit" name="price_per_unit" placeholder="Contoh: 7000" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label fw-semibold">Deskripsi Layanan (Opsional)</label>
                        <textarea class="form-control" id="create_description" name="description" rows="3" placeholder="Jelaskan rincian layanan, estimasi waktu, atau keunggulan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Layanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals Edit & Delete (Iterated per service) -->
@foreach($services as $service)
    <!-- Modal Edit Service -->
    <div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1" aria-labelledby="editServiceModalLabel{{ $service->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('admin.services.update', $service->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold" id="editServiceModalLabel{{ $service->id }}">
                            <i class="fa-solid fa-pen-to-square text-warning me-2"></i>Edit Paket Layanan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="mb-3">
                            <label for="edit_service_name_{{ $service->id }}" class="form-label fw-semibold">Nama Layanan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_service_name_{{ $service->id }}" name="service_name" value="{{ $service->service_name }}" required maxlength="100">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="edit_category_{{ $service->id }}" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_category_{{ $service->id }}" name="category" required>
                                    <option value="Kiloan" {{ $service->category === 'Kiloan' ? 'selected' : '' }}>Kiloan</option>
                                    <option value="Satuan" {{ $service->category === 'Satuan' ? 'selected' : '' }}>Satuan</option>
                                    <option value="Express" {{ $service->category === 'Express' ? 'selected' : '' }}>Express</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_unit_type_{{ $service->id }}" class="form-label fw-semibold">Satuan Unit <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_unit_type_{{ $service->id }}" name="unit_type" required>
                                    <option value="Kg" {{ $service->unit_type === 'Kg' ? 'selected' : '' }}>Kg</option>
                                    <option value="Pcs" {{ $service->unit_type === 'Pcs' ? 'selected' : '' }}>Pcs</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price_{{ $service->id }}" class="form-label fw-semibold">Harga Satuan (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-secondary">Rp</span>
                                <input type="number" class="form-control" id="edit_price_{{ $service->id }}" name="price_per_unit" value="{{ $service->price_per_unit }}" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_desc_{{ $service->id }}" class="form-label fw-semibold">Deskripsi Layanan (Opsional)</label>
                            <textarea class="form-control" id="edit_desc_{{ $service->id }}" name="description" rows="3">{{ $service->description }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Perbarui Layanan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Delete Service -->
    <div class="modal fade" id="deleteServiceModal{{ $service->id }}" tabindex="-1" aria-labelledby="deleteServiceModalLabel{{ $service->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-danger" id="deleteServiceModalLabel{{ $service->id }}">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>Konfirmasi Hapus Layanan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <p class="mb-1">Apakah Anda yakin ingin menghapus paket layanan <strong>{{ $service->service_name }}</strong>?</p>
                        <p class="text-secondary small mb-0">Paket yang dihapus tidak akan lagi muncul pada katalog pemesanan pelanggan.</p>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

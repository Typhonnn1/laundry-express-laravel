@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header Rekapitulasi -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rekapitulasi Seluruh Transaksi Laundry</h1>
            <p class="text-sm text-gray-500">Tabel rekapitulasi pesanan masuk tanpa filter sesuai ketentuan pengujian Serkom</p>
        </div>
    </div>

    <!-- Ringkasan Statistik Kartu Atas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-lg shadow border-l-4 border-blue-600">
            <div class="text-xs font-semibold text-gray-500 uppercase">Total Pesanan Masuk</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $orders->count() }} Transaksi</div>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-l-4 border-yellow-500">
            <div class="text-xs font-semibold text-gray-500 uppercase">Total Berat Cucian</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($totalWeight, 1) }} Kg</div>
        </div>
        <div class="bg-white p-5 rounded-lg shadow border-l-4 border-green-600">
            <div class="text-xs font-semibold text-gray-500 uppercase">Total Pendapatan Laundry</div>
            <div class="text-2xl font-bold text-green-700 mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Tabel Rekapitulasi Pesanan Tanpa Filter -->
    <div class="bg-white p-6 rounded-lg shadow border border-gray-100 overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-gray-100 text-gray-700 text-xs uppercase tracking-wider">
                    <th class="py-3 px-4 border-b">No. Nota</th>
                    <th class="py-3 px-4 border-b">Tanggal Masuk</th>
                    <th class="py-3 px-4 border-b">Nama Pelanggan</th>
                    <th class="py-3 px-4 border-b">Paket Layanan</th>
                    <th class="py-3 px-4 border-b">Berat (Kg)</th>
                    <th class="py-3 px-4 border-b">Total Biaya</th>
                    <th class="py-3 px-4 border-b text-center">Status</th>
                    <th class="py-3 px-4 border-b text-center">Ubah Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($orders as $o)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-bold text-blue-700">#{{ $o->id }}</td>
                        <td class="py-3 px-4 text-gray-600">{{ $o->order_date ? $o->order_date->format('d/m/Y') : '-' }}</td>
                        <td class="py-3 px-4 font-semibold text-gray-900">{{ $o->customer_name }}</td>
                        <td class="py-3 px-4 text-gray-800">{{ $o->package->name ?? 'Paket Dihapus' }}</td>
                        <td class="py-3 px-4 text-gray-700 font-semibold">{{ $o->weight }} kg</td>
                        <td class="py-3 px-4 font-bold text-green-700">Rp {{ number_format($o->total_price, 0, ',', '.') }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($o->status === 'Baru')
                                <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-1 rounded-full font-bold">Baru</span>
                            @elseif($o->status === 'Diproses')
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2.5 py-1 rounded-full font-bold">Diproses</span>
                            @elseif($o->status === 'Selesai')
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-1 rounded-full font-bold">Selesai</span>
                            @else
                                <span class="bg-purple-100 text-purple-800 text-xs px-2.5 py-1 rounded-full font-bold">Diambil</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('admin.orders.updateStatus', $o->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="status" onchange="this.form.submit()"
                                        class="text-xs border border-gray-300 rounded px-2 py-1 bg-white focus:ring-1 focus:ring-blue-500 focus:outline-none cursor-pointer">
                                    <option value="Baru" {{ $o->status === 'Baru' ? 'selected' : '' }}>Baru</option>
                                    <option value="Diproses" {{ $o->status === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                                    <option value="Selesai" {{ $o->status === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                                    <option value="Diambil" {{ $o->status === 'Diambil' ? 'selected' : '' }}>Diambil</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-400">
                            Belum ada riwayat transaksi pemesanan laundry yang masuk.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
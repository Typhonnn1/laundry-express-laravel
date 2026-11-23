@extends('layouts.app')

@section('content')
<div class="bg-white p-6 rounded-lg shadow border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Master Paket Layanan Laundry</h1>
            <p class="text-sm text-gray-500">Kelola daftar layanan tarif cuci dan durasi pengerjaan</p>
        </div>
        <a href="{{ route('admin.packages.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded shadow transition">
            + Tambah Paket Baru
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 text-xs uppercase tracking-wider">
                    <th class="py-3 px-4 border-b">ID</th>
                    <th class="py-3 px-4 border-b">Nama Paket</th>
                    <th class="py-3 px-4 border-b">Tarif per Kg</th>
                    <th class="py-3 px-4 border-b">Estimasi</th>
                    <th class="py-3 px-4 border-b">Deskripsi</th>
                    <th class="py-3 px-4 border-b text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse($packages as $p)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-3 px-4 font-semibold text-gray-600">#{{ $p->id }}</td>
                        <td class="py-3 px-4 font-bold text-gray-900">{{ $p->name }}</td>
                        <td class="py-3 px-4 text-green-700 font-bold">Rp {{ number_format($p->price_per_kg, 0, ',', '.') }} / kg</td>
                        <td class="py-3 px-4 text-gray-600">⏱️ {{ $p->estimated_duration }}</td>
                        <td class="py-3 px-4 text-gray-500 text-xs max-w-xs truncate">{{ $p->description }}</td>
                        <td class="py-3 px-4 text-center space-x-2">
                            <a href="{{ route('admin.packages.edit', $p->id) }}"
                               class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-2.5 py-1.5 rounded transition">
                                Edit
                            </a>
                            <form action="{{ route('admin.packages.destroy', $p->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket {{ $p->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="bg-red-500 hover:bg-red-600 text-white text-xs px-2.5 py-1.5 rounded transition">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-400">
                            Belum ada data paket laundry. Silakan klik tombol '+ Tambah Paket Baru'.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
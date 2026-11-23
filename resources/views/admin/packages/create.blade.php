@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow border border-gray-100">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Tambah Paket Laundry Baru</h1>
        <p class="text-sm text-gray-500">Masukkan detail layanan tarif cucian baru</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm rounded">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.packages.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Paket Layanan</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="Contoh: Cuci Komplit Wangi">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Tarif per Kilogram (Rp)</label>
                <input type="number" name="price_per_kg" value="{{ old('price_per_kg') }}" min="1000" step="500" required
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                       placeholder="Contoh: 8000">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Estimasi Durasi Pengerjaan</label>
                <input type="text" name="estimated_duration" value="{{ old('estimated_duration') }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                       placeholder="Contoh: 2 Hari / 24 Jam">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi &amp; Fasilitas Paket</label>
            <textarea name="description" rows="3" required
                      class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                      placeholder="Jelaskan jenis sabun, pewangi, setrika uap, dll.">{{ old('description') }}</textarea>
        </div>

        <div class="flex justify-end space-x-3 pt-4">
            <a href="{{ route('admin.packages.index') }}"
               class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition text-sm">
                Batal
            </a>
            <button type="submit"
                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded shadow transition text-sm">
                Simpan Paket
            </button>
        </div>
    </form>
</div>
@endsection
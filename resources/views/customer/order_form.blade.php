@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow border border-gray-100">
    <div class="mb-6 border-b pb-4">
        <h1 class="text-2xl font-bold text-gray-800">Formulir Pemesanan Laundry</h1>
        <p class="text-sm text-gray-500">Lengkapi data berat cucian Anda untuk membuat nota pesanan</p>
    </div>

    <!-- Ringkasan Paket Terpilih -->
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg flex justify-between items-center">
        <div>
            <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Paket Dipilih:</div>
            <div class="text-lg font-bold text-gray-900">{{ $package->name }}</div>
            <div class="text-xs text-gray-600">⏱️ Estimasi: {{ $package->estimated_duration }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-500">Tarif per Kg:</div>
            <div class="text-xl font-extrabold text-blue-700">Rp {{ number_format($package->price_per_kg, 0, ',', '.') }}</div>
        </div>
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

    <form method="POST" action="{{ route('customer.order.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="package_id" value="{{ $package->id }}">

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Pemesan</label>
            <input type="text" name="customer_name" value="{{ old('customer_name', Auth::user()->name) }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Berat Timbangan Cucian (Kg)</label>
            <input type="number" id="weight" name="weight" value="{{ old('weight', 2) }}" min="0.5" step="any" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   oninput="hitungTotal()">
            <p class="text-xs text-gray-500 mt-1">Gunakan titik untuk desimal, contoh: 2.5 kg (minimal 0.5 kg)</p>
        </div>

        <!-- Live Preview Perkiraan Total Biaya -->
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg flex justify-between items-center">
            <span class="text-sm font-semibold text-gray-700">Perkiraan Total Biaya:</span>
            <span id="preview_total" class="text-2xl font-extrabold text-green-600">Rp 0</span>
        </div>

        <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow transition duration-200">
            ✅ Konfirmasi &amp; Simpan Pesanan Laundry
        </button>
    </form>
</div>

<script>
    // Live Calculator Perkiraan Biaya Frontend (Murni UX Preview)
    const tarifPerKg = {{ $package->price_per_kg }};
    function hitungTotal() {
        const berat = parseFloat(document.getElementById('weight').value) || 0;
        const total = Math.round(berat * tarifPerKg);
        document.getElementById('preview_total').innerText = 'Rp ' + total.toLocaleString('id-ID');
    }
    // Hitung otomatis saat halaman dimuat
    hitungTotal();
</script>
@endsection
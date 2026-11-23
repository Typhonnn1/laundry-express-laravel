@extends('layouts.app')

@section('content')
<div class="mb-8 text-center">
    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Katalog Layanan Laundry</h1>
    <p class="text-gray-600 mt-2">Pilih paket laundry terbaik sesuai kebutuhan pakaian Anda</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($packages as $p)
        <div class="bg-white rounded-xl shadow-md border border-gray-100 hover:shadow-lg transition p-6 flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-xl font-bold text-gray-900">{{ $p->name }}</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded-full">
                        ⏱️ {{ $p->estimated_duration }}
                    </span>
                </div>

                <div class="my-4">
                    <span class="text-3xl font-extrabold text-blue-600">Rp {{ number_format($p->price_per_kg, 0, ',', '.') }}</span>
                    <span class="text-gray-500 text-sm">/ kg</span>
                </div>

                <p class="text-gray-600 text-sm leading-relaxed mb-6">
                    {{ $p->description }}
                </p>
            </div>

            <a href="{{ route('customer.order.create', $p->id) }}"
               class="w-full block text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow transition duration-200">
                🧺 Pilih &amp; Pesan Sekarang
            </a>
        </div>
    @empty
        <div class="col-span-3 text-center py-12 bg-white rounded-lg shadow text-gray-500">
            Saat ini belum ada paket laundry yang tersedia.
        </div>
    @endforelse
</div>
@endsection
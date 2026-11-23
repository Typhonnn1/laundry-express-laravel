@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md border border-gray-100">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Masuk ke Sistem Laundry</h2>
        <p class="text-sm text-gray-500 mt-1">Silakan masukkan email dan kata sandi Anda</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 border-l-4 border-red-500 text-red-700 text-sm rounded">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="nama@email.com">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Kata Sandi</label>
            <input type="password" name="password" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="••••••••">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded shadow transition duration-200">
            Masuk Sekarang
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-600">
        Belum punya akun? <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Daftar Akun Baru</a>
    </div>

    <!-- Informasi Akun Penguji Serkom -->
    <div class="mt-6 p-3 bg-blue-50 rounded border border-blue-200 text-xs text-blue-800">
        <b>💡 Akun Penguji Ujian BNSP:</b><br>
        &bull; <b>Admin:</b> admin@laundry.com | pass: admin123<br>
        &bull; <b>Customer:</b> customer@gmail.com | pass: customer123
    </div>
</div>
@endsection
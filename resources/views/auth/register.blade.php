@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md border border-gray-100">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Pendaftaran Akun Baru</h2>
        <p class="text-sm text-gray-500 mt-1">Daftar untuk mulai memesan layanan laundry</p>
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

    <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="Nama Anda">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="nama@email.com">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Kata Sandi (Min. 6 Karakter)</label>
            <input type="password" name="password" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="••••••••">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
            <input type="password" name="password_confirmation" required
                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none"
                   placeholder="••••••••">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded shadow transition duration-200">
            Daftar Sekarang
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-600">
        Sudah memiliki akun? <a href="{{ route('login') }}" class="text-blue-600 font-semibold hover:underline">Masuk di sini</a>
    </div>
</div>
@endsection
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Sistem Laundry' }} - Serkom BNSP</title>
    <!-- Tailwind CSS CDN Mandiri -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex flex-col">

    <!-- Navigasi Bar Utama -->
    <nav class="bg-blue-600 text-white shadow-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-3">
                    <span class="text-2xl">🧺</span>
                    <a href="/" class="text-xl font-bold tracking-wide hover:text-blue-100">Sistem Laundry</a>
                </div>

                <div class="flex items-center space-x-4">
                    @auth
                        @if(Auth::user()->role === 'admin')
                            <a href="{{ route('admin.packages.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">📦 Master Paket</a>
                            <a href="{{ route('admin.orders.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">📋 Rekap Pesanan</a>
                        @else
                            <a href="{{ route('customer.catalog') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition">🧺 Katalog Layanan</a>
                        @endif

                        <span class="text-xs bg-blue-800 px-2.5 py-1 rounded-full font-semibold">
                            👤 {{ Auth::user()->name }} ({{ strtoupper(Auth::user()->role) }})
                        </span>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1.5 rounded transition">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium hover:underline">Login</a>
                        <a href="{{ route('register') }}" class="bg-white text-blue-600 text-sm font-semibold px-3 py-1.5 rounded shadow hover:bg-gray-100 transition">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Konten Utama Halaman -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Message Notifikasi Sukses -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-800 rounded shadow-sm">
                <b>Sukses:</b> {{ session('success') }}
            </div>
        @endif

        <!-- Flash Message Notifikasi Error -->
        @if(session('error'))
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-800 rounded shadow-sm">
                <b>Error:</b> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer Aplikasi -->
    <footer class="bg-gray-800 text-gray-300 text-center py-4 text-xs mt-auto">
        &copy; {{ date('Y') }} Sistem Manajemen Laundry &bull; Ujian Sertifikasi Kompetensi Pemrograman Web BNSP
    </footer>

</body>
</html>
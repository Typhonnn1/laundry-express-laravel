@extends('layouts.base')

@section('title', 'Masuk Petugas & Admin - Laundry Express')

@section('body')
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 50%, #e0f2fe 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                
                <!-- Main Login Card -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <!-- Brand Accent Header Bar -->
                    <div class="bg-hero-gradient p-2"></div>

                    <div class="card-body p-4 p-sm-5">
                        
                        <!-- Brand Identity & Header -->
                        <div class="text-center mb-4">
                            <div class="bg-primary text-white rounded-3 shadow-sm d-inline-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px;">
                                <i class="fa-solid fa-shirt fs-3"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">Laundry Express</h4>
                            <p class="text-secondary small mb-0">Portal Masuk Petugas & Administrator</p>
                        </div>

                        <!-- Flash Success Alert (e.g. Logout) -->
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center mb-4" role="alert">
                                <i class="fa-solid fa-circle-check fs-5 text-success me-2"></i>
                                <div class="small flex-grow-1">{{ session('success') }}</div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- General Flash Error Alert -->
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center mb-4" role="alert">
                                <i class="fa-solid fa-circle-exclamation fs-5 text-danger me-2"></i>
                                <div class="small flex-grow-1">{{ session('error') }}</div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- Validation Errors Alert -->
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                                <div class="d-flex align-items-start">
                                    <i class="fa-solid fa-triangle-exclamation fs-5 text-danger me-2 mt-1"></i>
                                    <div class="small flex-grow-1">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- Login Form -->
                        <form method="POST" action="{{ route('login') }}" novalidate>
                            @csrf

                            <!-- Username Field -->
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold text-secondary small">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="fa-solid fa-user"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        class="form-control border-start-0 ps-0 @error('username') is-invalid @enderror" 
                                        id="username" 
                                        name="username" 
                                        value="{{ old('username') }}" 
                                        placeholder="Masukkan username" 
                                        required 
                                        autofocus 
                                        autocomplete="username"
                                    >
                                </div>
                                @error('username')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password Field -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="password" class="form-label fw-semibold text-secondary small mb-1">Password</label>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>
                                    <input 
                                        type="password" 
                                        class="form-control border-start-0 border-end-0 ps-0 @error('password') is-invalid @enderror" 
                                        id="password" 
                                        name="password" 
                                        placeholder="Masukkan password" 
                                        required 
                                        autocomplete="current-password"
                                    >
                                    <button class="btn btn-outline-secondary border-start-0 text-muted" type="button" id="togglePasswordBtn" title="Tampilkan/Sembunyikan Password">
                                        <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Remember Me Checkbox -->
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label text-secondary small user-select-none" for="remember">
                                    Ingat saya di perangkat ini
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span>Masuk ke Dashboard</span>
                            </button>
                        </form>

                        <!-- Demo Credential Information Hint -->
                        <div class="bg-light border border-dashed rounded-3 p-3 mt-4 text-center">
                            <div class="text-secondary small mb-1">
                                <i class="fa-solid fa-shield-halved text-primary me-1"></i>
                                <strong>Akun Admin Default:</strong>
                            </div>
                            <div class="font-monospace small text-dark">
                                Username: <span class="fw-bold">admin</span> &bull; Password: <span class="fw-bold">admin123</span>
                            </div>
                        </div>

                        <!-- Back to Customer Portal Link -->
                        <div class="text-center mt-4">
                            <a href="{{ url('/') }}" class="text-decoration-none text-secondary small hover-primary d-inline-flex align-items-center">
                                <i class="fa-solid fa-arrow-left me-1"></i>
                                <span>Kembali ke Beranda Pelanggan</span>
                            </a>
                        </div>

                    </div>
                </div>

                <!-- Footer Copyright Meta -->
                <div class="text-center mt-3 text-secondary small">
                    &copy; {{ date('Y') }} Laundry Express. Hak Cipta Dilindungi.
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleIcon.classList.toggle('fa-eye', !isPassword);
                toggleIcon.classList.toggle('fa-eye-slash', isPassword);
            });
        }
    });
</script>
@endpush

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman formulir login.
     */
    public function showLogin()
    {
        // Jika sudah login, langsung alihkan sesuai role pengguna
        if (Auth::check()) {
            return Auth::user()->role === 'admin'
                ? redirect()->route('admin.packages.index')
                : redirect()->route('customer.catalog');
        }
        return view('auth.login');
    }

    /**
     * Proses autentikasi login pengguna.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Pengalihan berdasarkan role pengguna
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.packages.index')
                    ->with('success', 'Selamat datang Admin!');
            }

            return redirect()->route('customer.catalog')
                ->with('success', 'Selamat datang di Sistem Laundry!');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah!',
        ])->onlyInput('email');
    }

    /**
     * Tampilkan formulir registrasi pelanggan baru.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return Auth::user()->role === 'admin'
                ? redirect()->route('admin.packages.index')
                : redirect()->route('customer.catalog');
        }
        return view('auth.register');
    }

    /**
     * Proses pendaftaran akun pelanggan baru.
     */
    public function register(Request $request)
    {
        if (Auth::check()) {
            return Auth::user()->role === 'admin'
                ? redirect()->route('admin.packages.index')
                : redirect()->route('customer.catalog');
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'customer', // Pendaftar umum otomatis sebagai customer
        ]);

        Auth::login($user);

        return redirect()->route('customer.catalog')
            ->with('success', 'Pendaftaran akun berhasil! Selamat datang di Sistem Laundry.');
    }

    /**
     * Proses logout pengguna.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}
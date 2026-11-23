<?php

namespace App\Http\Controllers;

use App\Models\LaundryPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    /**
     * Helper proteksi hak akses khusus Administrator.
     */
    private function checkAdmin()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Hanya Administrator yang dapat mengelola data paket!');
        }
    }

    /**
     * Tampilkan daftar seluruh paket laundry.
     */
    public function index()
    {
        $this->checkAdmin();
        $packages = LaundryPackage::latest()->get();
        return view('admin.packages.index', compact('packages'));
    }

    /**
     * Tampilkan formulir tambah paket baru.
     */
    public function create()
    {
        $this->checkAdmin();
        return view('admin.packages.create');
    }

    /**
     * Simpan data paket baru ke basis data.
     */
    public function store(Request $request)
    {
        $this->checkAdmin();
        $request->validate([
            'name'               => 'required|string|max:255',
            'price_per_kg'       => 'required|numeric|min:1000',
            'estimated_duration' => 'required|string|max:100',
            'description'        => 'required|string',
        ]);

        LaundryPackage::create($request->all());

        return redirect()->route('admin.packages.index')
            ->with('success', 'Paket laundry baru berhasil ditambahkan!');
    }

    /**
     * Tampilkan detail satu paket laundry (alihkankan ke formulir edit).
     */
    public function show(LaundryPackage $package)
    {
        $this->checkAdmin();
        return redirect()->route('admin.packages.edit', $package);
    }

    /**
     * Tampilkan formulir edit paket laundry.
     */
    public function edit(LaundryPackage $package)
    {
        $this->checkAdmin();
        return view('admin.packages.edit', compact('package'));
    }

    /**
     * Perbarui data paket laundry di basis data.
     */
    public function update(Request $request, LaundryPackage $package)
    {
        $this->checkAdmin();
        $request->validate([
            'name'               => 'required|string|max:255',
            'price_per_kg'       => 'required|numeric|min:1000',
            'estimated_duration' => 'required|string|max:100',
            'description'        => 'required|string',
        ]);

        $package->update($request->all());

        return redirect()->route('admin.packages.index')
            ->with('success', 'Data paket laundry berhasil diperbarui!');
    }

    /**
     * Hapus paket laundry dari basis data.
     */
    public function destroy(LaundryPackage $package)
    {
        $this->checkAdmin();
        $package->delete();

        return redirect()->route('admin.packages.index')
            ->with('success', 'Paket laundry berhasil dihapus!');
    }
}
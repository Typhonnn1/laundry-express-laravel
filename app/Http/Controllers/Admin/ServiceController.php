<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Display a listing of all service packages.
     */
    public function index(): View
    {
        $services = Service::latest()->get();

        return view('admin.services.index', compact('services'));
    }

    /**
     * Store a newly created service package in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_name' => ['required', 'string', 'max:100', 'regex:/[^\s]/'],
            'category' => ['required', 'string', Rule::in(Service::CATEGORIES)],
            'price_per_unit' => ['required', 'integer', 'min:0'],
            'unit_type' => ['required', 'string', Rule::in(Service::UNIT_TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'service_name.required' => 'Nama layanan wajib diisi.',
            'service_name.max' => 'Nama layanan maksimal 100 karakter.',
            'service_name.regex' => 'Nama layanan tidak boleh hanya berisi spasi.',
            'category.required' => 'Kategori layanan wajib dipilih.',
            'category.in' => 'Kategori harus salah satu dari: Kiloan, Satuan, atau Express.',
            'price_per_unit.required' => 'Harga per satuan wajib diisi.',
            'price_per_unit.integer' => 'Harga per satuan harus berupa angka bulat.',
            'price_per_unit.min' => 'Harga per satuan tidak boleh bernilai negatif.',
            'unit_type.required' => 'Satuan unit wajib dipilih.',
            'unit_type.in' => 'Satuan unit harus berupa Kg atau Pcs.',
        ]);

        Service::create($validated);

        return redirect('/admin/layanan')->with('success', 'Paket layanan berhasil ditambahkan.');
    }

    /**
     * Update the specified service package in storage.
     */
    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'service_name' => ['required', 'string', 'max:100', 'regex:/[^\s]/'],
            'category' => ['required', 'string', Rule::in(Service::CATEGORIES)],
            'price_per_unit' => ['required', 'integer', 'min:0'],
            'unit_type' => ['required', 'string', Rule::in(Service::UNIT_TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'service_name.required' => 'Nama layanan wajib diisi.',
            'service_name.max' => 'Nama layanan maksimal 100 karakter.',
            'service_name.regex' => 'Nama layanan tidak boleh hanya berisi spasi.',
            'category.required' => 'Kategori layanan wajib dipilih.',
            'category.in' => 'Kategori harus salah satu dari: Kiloan, Satuan, atau Express.',
            'price_per_unit.required' => 'Harga per satuan wajib diisi.',
            'price_per_unit.integer' => 'Harga per satuan harus berupa angka bulat.',
            'price_per_unit.min' => 'Harga per satuan tidak boleh bernilai negatif.',
            'unit_type.required' => 'Satuan unit wajib dipilih.',
            'unit_type.in' => 'Satuan unit harus berupa Kg atau Pcs.',
        ]);

        $service->update($validated);

        return redirect('/admin/layanan')->with('success', 'Paket layanan berhasil diperbarui.');
    }

    /**
     * Remove the specified service package from storage.
     */
    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect('/admin/layanan')->with('success', 'Paket layanan berhasil dihapus.');
    }
}

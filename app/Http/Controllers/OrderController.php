<?php

namespace App\Http\Controllers;

use App\Models\LaundryPackage;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * 1. Tampilkan katalog paket laundry untuk Customer.
     */
    public function catalog()
    {
        $packages = LaundryPackage::all();
        return view('customer.catalog', compact('packages'));
    }

    /**
     * 2. Tampilkan formulir pemesanan untuk paket yang dipilih.
     */
    public function createOrder($package_id)
    {
        $package = LaundryPackage::findOrFail($package_id);
        return view('customer.order_form', compact('package'));
    }

    /**
     * 3. Simpan transaksi pesanan laundry pelanggan.
     */
    public function storeOrder(Request $request)
    {
        $request->validate([
            'package_id'    => 'required|exists:laundry_packages,id',
            'customer_name' => 'required|string|max:255',
            'weight'        => 'required|numeric|min:0.5',
        ]);

        $package = LaundryPackage::findOrFail($request->package_id);

        // Kalkulasi total harga di backend server-side (aman dari manipulasi form pengguna)
        $total_price = (int) round($package->price_per_kg * $request->weight);

        $order = Order::create([
            'user_id'       => Auth::id(),
            'package_id'    => $package->id,
            'customer_name' => $request->customer_name,
            'weight'        => $request->weight,
            'total_price'   => $total_price,
            'status'        => 'Baru',
            'order_date'    => now()->toDateString(),
        ]);

        return redirect()->route('customer.catalog')
            ->with('success', "Pesanan Laundry berhasil dibuat dengan No. Nota #{$order->id}! Total Biaya: Rp " . number_format($total_price, 0, ',', '.'));
    }

    /**
     * 4. Rekapitulasi pesanan masuk tanpa filter (Sisi Administrator).
     */
    public function adminOrders()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Hanya Administrator yang dapat melihat rekapan pesanan!');
        }

        // Unfiltered recap table dengan eager loading untuk performa optimal
        $orders = Order::with('package', 'user')->latest()->get();
        $totalRevenue = $orders->sum('total_price');
        $totalWeight = $orders->sum('weight');

        return view('admin.orders.index', compact('orders', 'totalRevenue', 'totalWeight'));
    }

    /**
     * 5. Ubah status progres pesanan laundry (Sisi Administrator).
     */
    public function updateStatus(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:Baru,Diproses,Selesai,Diambil',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return back()->with('success', "Status pesanan #{$order->id} berhasil diubah menjadi {$order->status}!");
    }
}
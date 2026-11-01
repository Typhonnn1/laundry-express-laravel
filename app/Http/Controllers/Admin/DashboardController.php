<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the administrative overview dashboard with operational metrics and recent transactions.
     */
    public function index(): View
    {
        // 1. Metric: Total Orders
        $totalOrders = Order::count();

        // 2. Metric: Total Revenue (Omset)
        $totalRevenue = (int) Order::sum('total_price');

        // 3. Metric: Operational Status Breakdown
        $countAntrean = Order::where('status', Order::STATUS_ANTREAN)->count();
        $countDicuci = Order::where('status', Order::STATUS_DICUCI)->count();
        $countSelesai = Order::where('status', Order::STATUS_SELESAI)->count();

        // 4. Recent Transactions (Eager load relations, newest first)
        $recentOrders = Order::with('orderDetails.service')
            ->latest('id')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalOrders',
            'totalRevenue',
            'countAntrean',
            'countDicuci',
            'countSelesai',
            'recentOrders'
        ));
    }
}

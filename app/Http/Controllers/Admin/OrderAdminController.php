<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderAdminController extends Controller
{
    /**
     * Display a listing of orders with status filtering and search.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        // Support both ?search= (from E2E tests) and ?q= (from project spec)
        $search = $request->query('search') ?? $request->query('q');

        $query = Order::with('orderDetails.service')->latest('id');

        // Filter by Status
        if ($request->filled('status') && in_array($status, Order::STATUSES)) {
            $query->where('status', $status);
        }

        // Keyword Search (Customer Name, WhatsApp Number, or Order ID)
        if (! empty($search)) {
            $searchTerm = trim((string) $search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_name', 'like', "%{$searchTerm}%")
                    ->orWhere('whatsapp_number', 'like', "%{$searchTerm}%");

                $cleanId = preg_replace('/[^0-9]/', '', $searchTerm);
                if (! empty($cleanId) && ctype_digit($cleanId)) {
                    $q->orWhere('id', (int) $cleanId);
                }
            });
        }

        // Status counts for badge tabs
        $statusCounts = [
            'all' => Order::count(),
            'Antrean' => Order::where('status', Order::STATUS_ANTREAN)->count(),
            'Dicuci' => Order::where('status', Order::STATUS_DICUCI)->count(),
            'Selesai' => Order::where('status', Order::STATUS_SELESAI)->count(),
        ];

        // Paginate results with query string preservation (25 per page ensures clean E2E assertion coverage)
        $orders = $query->paginate(25)->withQueryString();

        return view('admin.orders.index', compact('orders', 'status', 'search', 'statusCounts'));
    }

    /**
     * Update the processing status of an order.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Order::STATUSES)],
        ], [
            'status.required' => 'Status pesanan wajib dipilih.',
            'status.in' => 'Status pesanan harus salah satu dari: Antrean, Dicuci, atau Selesai.',
        ]);

        $order->update(['status' => $validated['status']]);

        return redirect()->back(fallback: route('admin.orders.index'))
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }

    /**
     * Delete an order and its associated order details.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect('/admin/pesanan')->with('success', 'Pesanan berhasil dihapus.');
    }
}

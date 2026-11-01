<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display the customer portal homepage with service catalog, order form, and order tracking search.
     */
    public function index(Request $request): View
    {
        $services = Service::all();
        $search = $request->query('search');
        $trackedOrders = null;
        $searchPerformed = false;

        if ($request->filled('search')) {
            $searchTerm = trim((string) $search);
            $searchPerformed = true;

            $trackedOrders = Order::with('orderDetails.service')
                ->where(function ($query) use ($searchTerm) {
                    $query->where('whatsapp_number', $searchTerm)
                        ->orWhere('id', $searchTerm);

                    // Also support formatted ID like #1 or #LE-00001
                    $cleanId = preg_replace('/[^0-9]/', '', $searchTerm);
                    if (! empty($cleanId) && ctype_digit($cleanId)) {
                        $query->orWhere('id', (int) $cleanId);
                    }
                })
                ->latest()
                ->get();
        }

        return view('orders.index', compact('services', 'trackedOrders', 'search', 'searchPerformed'));
    }

    /**
     * Store a newly created order and order details in a database transaction with server-side price recalculation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => [
                'required',
                'string',
                'max:100',
                'regex:/[^\s]/',
            ],
            'whatsapp_number' => [
                'required',
                'string',
                'min:8',
                'max:20',
                'regex:/^(\+?62|0)[0-9]{8,18}$/',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
            'items.*.qty_or_weight' => [
                'required',
                'integer',
                'min:1',
                'max:100000',
            ],
        ], [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'customer_name.max' => 'Nama pelanggan maksimal 100 karakter.',
            'customer_name.regex' => 'Nama pelanggan tidak boleh hanya berisi spasi.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format 08... atau 62...',
            'items.required' => 'Minimal pilih satu layanan laundry.',
            'items.min' => 'Minimal pilih satu layanan laundry.',
            'items.*.service_id.required' => 'Layanan harus dipilih.',
            'items.*.service_id.exists' => 'Layanan yang dipilih tidak terdaftar.',
            'items.*.qty_or_weight.required' => 'Jumlah atau berat wajib diisi.',
            'items.*.qty_or_weight.integer' => 'Jumlah atau berat harus berupa angka bulat.',
            'items.*.qty_or_weight.min' => 'Jumlah atau berat minimal 1.',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $createdOrder = Order::create([
                'customer_name' => trim($validated['customer_name']),
                'whatsapp_number' => trim($validated['whatsapp_number']),
                'total_price' => 0,
                'status' => Order::STATUS_ANTREAN,
            ]);

            $calculatedGrandTotal = 0;

            foreach ($validated['items'] as $item) {
                $service = Service::lockForUpdate()->findOrFail($item['service_id']);
                $qty = (int) $item['qty_or_weight'];
                $itemSubtotal = $service->price_per_unit * $qty;
                $calculatedGrandTotal += $itemSubtotal;

                OrderDetail::create([
                    'order_id' => $createdOrder->id,
                    'service_id' => $service->id,
                    'qty_or_weight' => $qty,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            $createdOrder->update(['total_price' => $calculatedGrandTotal]);

            return $createdOrder;
        });

        return redirect('/nota/'.$order->id)->with('success', 'Pesanan laundry berhasil dibuat!');
    }

    /**
     * Display the specified order receipt (nota).
     */
    public function show(Order $order): View
    {
        $order->load(['orderDetails.service']);

        return view('orders.nota', compact('order'));
    }
}

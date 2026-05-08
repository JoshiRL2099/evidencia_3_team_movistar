<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicOrderController extends Controller
{
    public function lookup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => ['required', 'string'],
            'customer_number' => ['required', 'string'],
        ], [
            'invoice_number.required' => 'El número de pedido es obligatorio.',
            'customer_number.required' => 'El número de cliente es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoiceNumber = $request->input('invoice_number');
        $customerNumber = $request->input('customer_number');

        $order = Order::query()
            ->with([
                'customer',
                'deliveryAddress',
                'items.product',
                'photos',
            ])
            ->where('invoice_number', $invoiceNumber)
            ->where('is_deleted', false)
            ->where('status', '!=', 'DELETED')
            ->whereHas('customer', function ($query) use ($customerNumber) {
                $query->where('customer_number', $customerNumber);
            })
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'No se encontró un pedido con ese número para ese cliente.',
            ], 404);
        }

        $address = $order->deliveryAddress;

        $fullAddress = $address
            ? trim(collect([
                $address->street,
                $address->ext_number ? 'Ext. ' . $address->ext_number : null,
                $address->int_number ? 'Int. ' . $address->int_number : null,
                $address->neighborhood,
                $address->city,
                $address->state,
                $address->zip ? 'C.P. ' . $address->zip : null,
                $address->references ? 'Referencias: ' . $address->references : null,
            ])->filter()->implode(', '))
            : null;

        return response()->json([
            'message' => 'Pedido encontrado.',
            'data' => [
                'order_id' => $order->order_id,
                'invoice_number' => $order->invoice_number,
                'order_datetime' => optional($order->order_datetime)->format('Y-m-d H:i:s'),
                'status' => $order->status,
                'notes' => $order->notes,

                'customer' => [
                    'customer_id' => $order->customer?->customer_id,
                    'customer_number' => $order->customer?->customer_number,
                    'display_name' => $order->customer?->display_name,
                ],

                'address' => $fullAddress,

                'materials' => $order->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name ?? 'Producto sin nombre',
                        'quantity' => $item->quantity,
                        'unit' => $item->product?->unit,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->quantity * $item->unit_price,
                    ];
                })->values(),

                'photos' => $order->photos->map(function ($photo) {
                    // Use `url` as canonical field for photo location. Keep fallbacks for compatibility.
                    return [
                        'photo_id' => $photo->photo_id ?? null,
                        'url' => $photo->url ?? $photo->photo_path ?? $photo->path ?? null,
                        'type' => $photo->type ?? null,
                    ];
                })->values(),
            ],
        ]);
    }
}
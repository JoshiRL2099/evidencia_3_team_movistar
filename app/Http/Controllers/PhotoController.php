<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Authorization: only ADMIN, ROUTE and WAREHOUSE can upload evidence.
        $user = Auth::user();
        $role = $user->role->name ?? '';
        $allowed = ['ADMIN', 'ROUTE'];
        if (!in_array(strtoupper($role), $allowed, true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // Determine order id from route or payload
        $orderId = $request->route('id') ?? $request->input('order_id');
        if (empty($orderId)) {
            return response()->json(['message' => 'order_id es requerido.'], 400);
        }

        // Verify order exists (Order uses UUID primary key `order_id`)
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada.'], 404);
        }

        // Validate files: images only, max 5 MB each
        $rules = [
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['file', 'image', 'max:5120'], // max 5MB
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Validación fallida.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $saved = [];

        $files = $request->file('photos') ?? [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;

            // Store in public disk under photos/
            $path = $file->store('photos', 'public');
            $url = Storage::url($path); // returns /storage/...

            $photo = Photo::create([
                'url' => $url,
                'type' => $request->input('type', 'UNLOADED_EVIDENCE'),
                'order_id' => $order->order_id,
                'uploaded_by_user_id' => $user->user_id,
                'uploaded_at' => now(),
            ]);

            $saved[] = [
                'photo_id' => $photo->photo_id,
                'url' => $photo->url,
                'type' => $photo->type,
            ];
        }

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Fotos subidas correctamente.',
                'data' => $saved,
            ], 201);
        }

        return redirect()->back()->with('success', 'Fotos subidas correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Optional: allow admins to delete an evidence photo.
        $user = Auth::user();
        $role = $user->role->name ?? '';
        if (strtoupper($role) !== 'ADMIN') {
            return redirect()->back()->with('error', 'No autorizado.');
        }

        // Photo uses `photo_id` as primary key (UUID). Query explicitly.
        $photo = Photo::where('photo_id', $id)->firstOrFail();

        // Delete file from storage if present
        if (!empty($photo->url)) {
            $publicPath = parse_url($photo->url, PHP_URL_PATH);
            $storagePrefix = '/storage/';
            if (str_starts_with($publicPath, $storagePrefix)) {
                $relative = ltrim(substr($publicPath, strlen($storagePrefix)), '/');
                Storage::disk('public')->delete($relative);
            }
        }

        $photo->delete();

        return redirect()->back()->with('success', 'Evidencia eliminada.');
    }
}

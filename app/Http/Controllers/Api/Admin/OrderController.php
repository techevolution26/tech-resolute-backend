<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller {
     public function index(Request $request)
    {
        // load items and product data for convenience
        $orders = Order::with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Map to a simple view-model (avoid exposing unnecessary fields)
        $data = $orders->map(function ($o) {
            return [
                'id' => $o->id,
                'status' => $o->status,
                'total' => $o->total,
                'customer_name' => $o->customer_name,
                'customer_email' => $o->customer_email,
                'customer_phone' => $o->customer_phone,
                'shipping_address' => $o->shipping_address,
                'notes' => $o->notes,
                'created_at' => optional($o->created_at)->toDateTimeString(),
                'items' => $o->items->map(function ($it) {
                    $prod = $it->product;
                    return [
                        'id' => $it->id,
                        'product_id' => $it->product_id,
                        // prefer explicit item title, fallback to related product title
                        'title' => $it->title ?? ($prod->title ?? null),
                        'quantity' => $it->quantity,
                        'unit_price' => $it->unit_price,
                        'total_price' => $it->total_price,
                        // including a small product object for thumbnails / links
                        'product' => $prod ? [
                            'id' => $prod->id,
                            'title' => $prod->title,
                            // assuming  product model has image_url or image column
                            'image_url' => $prod->image_url ?? $prod->image ?? null,
                            'slug' => $prod->slug ?? null,
                        ] : null,
                    ];
                })->toArray(),
            ];
        });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $request->only([
            'product_id', 'quantity',
            'customer_name', 'customer_email', 'customer_phone',
            'shipping_address', 'notes'
        ]);

        $v = Validator::make($data, [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }

        $product = Product::findOrFail($data['product_id']);
        $qty = (int)$data['quantity'];
        $unitPrice = (float)($product->price ?? 0);
        $total = $unitPrice * $qty;

        DB::beginTransaction();
        try {
            $order = Order::create([
                'product_id' => $product->id,
                'status' => 'pending',
                'total' => $total,
                'quantity' => $qty,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'title' => $product->title,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $total,
            ]);

            DB::commit();

            // Build a local checkout_url pointing to the frontend processing page
            $frontend = env('FRONTEND_URL', config('app.url')); // set FRONTEND_URL in .env to your Next app origin
            $checkoutUrl = rtrim($frontend, '/') . '/orders/processing?orderId=' . $order->id;

            return response()->json([
                'id' => $order->id,
                'message' => 'Order created',
                'checkout_url' => $checkoutUrl,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order creation failed: '.$e->getMessage(), ['payload'=>$data]);
            return response()->json(['message'=>'Server error'], 500);
        }
    }

    public function show($id)
    {
        $order = Order::with('items')->find($id);
        if (!$order) return response()->json(['message'=>'Not found'], 404);
        return response()->json($order);
    }

    // simple test endpoint to mark order paid (local stub)
    public function pay($id)
    {
        $order = Order::find($id);
        if (!$order) return response()->json(['message'=>'Not found'], 404);
        $order->status = 'paid';
        $order->save();
        return response()->json(['message'=>'Order marked paid', 'id'=>$order->id]);
    }

    public function update(Request $r, $id) {
        $order = Order::findOrFail($id);
        $data = $r->validate(['status'=>'required|in:new,pending,contacted,completed,cancelled']);
        $order->update($data);
        return response()->json($order);
    }
}

<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class OrderController extends Controller
{

     public function index(Request $request)
    {
        // load items and product data for convenience
        $orders = Order::with(['items', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
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
}

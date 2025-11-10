<?php

// app/Http/Controllers/Api/V1/SellerOrdersController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class SellerOrdersController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // assume products have seller_id
        $orders = Order::whereHas('items.product', function($q) use ($user) {
            $q->where('seller_id', $user->id);
        })->with(['items.product'])->orderBy('created_at','desc')->get();

        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::with(['items.product'])->findOrFail($id);

        // enforce ownership (simple check)
        $owns = $order->items->contains(function($it) use ($user) {
            return isset($it->product) && ($it->product->seller_id == $user->id);
        });

        if (! $owns && ! $user->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($order);
    }
}

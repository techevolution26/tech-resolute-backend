<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Order;

class EnsureSellerOwnsOrder
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! ($user->is_seller ?? false)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden'], 403)
                : redirect()->route('login');
        }

        $orderId = $request->route('order') ?? $request->route('id');
        if (! $orderId) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Order id missing'], 400)
                : abort(400);
        }

        $order = Order::with('items.product')->find($orderId);
        if (! $order) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Order not found'], 404)
                : abort(404);
        }

        // Example ownership check: either order.seller_id === user.id OR any item.product.seller_id === user.id
        $owns = false;
        if (isset($order->seller_id) && $order->seller_id == $user->id) {
            $owns = true;
        } else {
            foreach ($order->items as $it) {
                if (isset($it->product) && isset($it->product->seller_id) && $it->product->seller_id == $user->id) {
                    $owns = true;
                    break;
                }
            }
        }

        if (! $owns) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden — you do not own this resource'], 403)
                : abort(403);
        }

        // attach resolved order if useful downstream
        $request->attributes->set('resolvedOrder', $order);

        return $next($request);
    }
}

<?php
// app/Http/Controllers/Api/V1/SellerProductController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class SellerProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $products = Product::where('seller_id', $user->id)->get();
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'title' => 'required|string',
            'price' => 'required|numeric',
        ]);
        $data['seller_id'] = $user->id;
        $product = Product::create($data);
        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $product = Product::where('id', $id)->where('seller_id', $user->id)->firstOrFail();
        $data = $request->validate([
            'title' => 'sometimes|string',
            'price' => 'sometimes|numeric',
        ]);
        $product->update($data);
        return response()->json($product);
    }
}

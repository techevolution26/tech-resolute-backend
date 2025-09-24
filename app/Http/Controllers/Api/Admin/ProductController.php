<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;

class ProductController extends Controller {
    public function index( Request $r ) {
        $per = ( int ) $r->get( 'per', 25 );
        return Product::with( [ 'category', 'seller', ] )->orderBy( 'created_at', 'desc' )->paginate( $per );
    }

  public function store(Request $r)
{
    $data = $r->validate([
        'title' => 'required|string|max:255',
        'slug' => 'nullable|string|unique:products,slug',
        'description' => 'nullable|string',
        'price' => 'required|numeric',
        'currency' => 'nullable|string',
        'condition' => 'nullable|string',
        'category_id' => 'nullable|exists:categories,id',
        'stock' => 'nullable|integer',
        'image' => 'nullable|image|max:2048',
    ]);

    if (empty($data['slug'])) {
        $data['slug'] = Str::slug($data['title']);
    }

    if ($r->user()) {
        $data['user_id'] = $r->user()->id;
    }

    // handle image upload
    if ($r->hasFile('image')) {
        $path = $r->file('image')->store('products', 'public'); // products/abc.jpg
        // Storage::url($path) -> "/storage/products/abc.jpg"
        // url(...) -> "http://your-app-url/storage/products/abc.jpg"
        $data['image_url'] = url(Storage::url($path));
    }

    $product = Product::create($data);
    return response()->json($product, 201);
}

    // show endpoint required by your admin edit page

    public function show( $id ) {
        $product = Product::with( [ 'category', 'seller' ] )->findOrFail( $id );
        return response()->json( $product );
    }




public function update(Request $r, $id)
{
    $product = Product::findOrFail($id);

    $data = $r->validate([
        'title' => 'sometimes|string|max:255',
        'slug' => "sometimes|string|unique:products,slug,{$id}",
        'description' => 'nullable|string',
        'price' => 'sometimes|numeric',
        'currency' => 'nullable|string',
        'condition' => 'nullable|string',
        'category_id' => 'nullable|exists:categories,id',
        'stock' => 'nullable|integer',
        'image' => 'nullable|image|max:2048',
    ]);

    if ($r->hasFile('image')) {
        // delete previous file if stored via storage link
        if ($product->image_url) {
            // image_url might be absolute like http://127.0.0.1:8000/storage/products/xxx.jpg
            // convert to disk path: remove APP_URL and leading '/storage/'
            $oldPath = str_replace(url('/'), '', $product->image_url);
            $oldPath = ltrim(str_replace('/storage/', '', $oldPath), '/');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $r->file('image')->store('products', 'public');
        $data['image_url'] = url(Storage::url($path));
    }

    if ($r->user() && empty($product->user_id)) {
        $data['user_id'] = $r->user()->id;
    }

    $product->update($data);
    return response()->json($product);
}

    public function destroy( $id ) {
        $product = Product::findOrFail( $id );

        // delete image file if present
        if ( $product->image_url ) {
            $oldPath = ltrim( str_replace( '/storage/', '', $product->image_url ), '/' );
            Storage::disk( 'public' )->delete( $oldPath );
        }

        $product->delete();

        return response()->json( [ 'deleted' => true ] );
    }
}

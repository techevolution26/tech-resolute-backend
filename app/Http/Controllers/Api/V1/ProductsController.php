<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductsController extends Controller {
    public function index( Request $request ) {
        $query = Product::with( 'category', 'seller' )->where( 'status', 'published' );

        if ( $q = $request->get( 'q' ) ) {
            $query->where( fn( $qb ) => $qb->where( 'title', 'like', "%{$q}%" )->orWhere( 'description', 'like', "%{$q}%" ) );
        }
        if ( $cat = $request->get( 'category' ) ) {
            $query->whereHas( 'category', fn( $qb ) => $qb->where( 'slug', $cat ) );
        }
        if ( $condition = $request->get( 'condition' ) ) {
            $query->where( 'condition', $condition );
        }

        $per = min( 50, ( int )$request->get( 'per', 12 ) );
        $products = $query->paginate( $per );

        return response()->json( $products );
    }
public function show($slug)
{
    try {
        $product = Product::with(['category', 'seller', 'images'])->where('slug', $slug)->firstOrFail();

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'published')
            ->limit(6)->get();

        return response()->json(['product' => $product, 'related' => $related], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
        return response()->json(['message' => 'Product not found'], 404);
    } catch (\Throwable $e) {
        Log::error('ProductsController@show error', ['slug' => $slug, 'message' => $e->getMessage()]);
        return response()->json(['message' => 'Server error'], 500);
    }
}


}

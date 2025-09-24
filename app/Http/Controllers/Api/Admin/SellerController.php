<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Seller;
use App\Mail\SellerApprovedMail;
use Illuminate\Support\Facades\Mail;

class SellerController extends Controller {
    public function index() {
        return Seller::orderBy( 'created_at', 'desc' )->paginate( 25 );
    }

    public function approve( Request $r, $id ) {
        $r->validate( [
            'notes' => 'nullable|string|max:2000',
            'notify_email' => 'nullable|boolean'
        ] );

        $seller = Seller::findOrFail( $id );
        $seller->approved = true;
        $seller->notes = $r->input( 'notes' );
        $seller->save();

        if ( $r->boolean( 'notify_email' ) && $seller->contact_email ) {
            Mail::to( $seller->contact_email )->queue( new SellerApprovedMail( $seller, $r->input( 'notes', '' ) ) );
        }

        return response()->json( $seller );
    }
}

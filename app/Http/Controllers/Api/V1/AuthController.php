<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {
    public function login( Request $request ) {
        $data = $request->validate( [
            'email' => 'required|email',
            'password' => 'required|string',
        ] );

        $user = User::where( 'email', $data[ 'email' ] )->first();

        if ( ! $user || ! Hash::check( $data[ 'password' ], $user->password ) || ! $user->is_admin ) {
            throw ValidationException::withMessages( [
                'email' => [ 'The provided credentials are incorrect or user is not admin.' ],
            ] );
        }

        $token = $user->createToken( 'admin-token' )->plainTextToken;

        return response()->json( [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_admin' => ( bool )$user->is_admin,
            ]
        ] );
    }

    // authenticated route to verify token

    public function me( Request $request ) {
        $user = $request->user();
        return response()->json( [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'is_admin' => ( bool )$user->is_admin,
        ] );
    }

    public function logout( Request $request ) {
        $request->user()->currentAccessToken()->delete();
        return response()->json( [ 'message' => 'Logged out' ] );
    }
}

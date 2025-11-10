<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Models\SellerApplication;
use App\Models\Seller;
use App\Models\User;
use App\Mail\SellerApprovedMail;

class AdminSellerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $per = (int) $request->get('per', 20);
        $q = SellerApplication::query()->orderBy('created_at', 'desc');

        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }

        $items = $q->paginate($per);

        return response()->json($items);
    }

    public function approve(Request $request, $id)
    {
        $app = SellerApplication::find($id);
        if (!$app) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        if ($app->status === 'approved') {
            return response()->json(['message' => 'Already approved'], 400);
        }

        // decide seller_type: keep what applicant requested, default 'one_time'
        $sellerType = $app->application_type ?? 'one_time';

        // create or find user by email
        if (!$app->email) {
            return response()->json(['message' => 'Application missing email'], 422);
        }

        $user = User::where('email', $app->email)->first();

        if (!$user) {
            // Create a new user with a random password (they will set with the link)
            $user = User::create([
                'name' => $app->contact_name,
                'email' => $app->email,
                // temporary password (we don't expose) - will be overridden by reset flow
                'password' => \Illuminate\Support\Facades\Hash::make(Str::random(24)),
                'is_seller' => true,
                'seller_type' => $sellerType,
                'seller_application_id' => $app->id,
            ]);
        } else {
            // Existing user: mark as seller
            $user->update([
                'is_seller' => true,
                'seller_type' => $sellerType,
                'seller_application_id' => $app->id,
            ]);
        }

        // mark application as approved
        $app->status = 'approved';
        $app->approved_at = now();
        $app->save();

        // create password reset token for this user (so they can set password)
        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker(); // default broker
        $token = $broker->createToken($user);

        // Build a frontend URL for setting password. Set this env var in your .env:
        // NEXT_PUBLIC_APP_URL=https://app.example.com
        $frontend = rtrim(env('NEXT_PUBLIC_APP_URL', config('app.url')), '/');
        $setPasswordUrl = $frontend . '/seller/set-password?token=' . urlencode($token) . '&email=' . urlencode($user->email);

        // send mail (queue or sync)
       try {
    // try to queue the mailable (non-blocking)
         Mail::to($user->email)->queue(new SellerApprovedMail($user, $setPasswordUrl, $sellerType));
         } catch (\Throwable $e) {
    // if queueing failed for some reason, try to send synchronously as a safe fallback
           try {
            Mail::to($user->email)->send(new SellerApprovedMail($user, $setPasswordUrl, $sellerType));
           } catch (\Throwable $e2) {
             Log::error('Failed to send seller approved mail (queue & sync failed): '.$e2->getMessage(), [
            'user_id' => $user->id,
            'application_id' => $app->id,
          ]);}
            // Continue — we still return success but notify admin to retry mail if needed
        }

        // mark verified time for long_term sellers (optional)
        if ($sellerType === 'long_term') {
            $user->seller_verified_at = now();
            $user->save();
        }

        return response()->json([
            'message' => 'Application approved and invitation sent',
            'user_id' => $user->id,
            'application_id' => $app->id,
        ], 200);
    }
    }


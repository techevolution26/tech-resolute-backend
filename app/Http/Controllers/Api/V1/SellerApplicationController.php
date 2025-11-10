<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerApplicationRequest;
use App\Models\SellerApplication;
use App\Jobs\NotifyAdminOfSellerApplication;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SellerApprovedMail;
use Illuminate\Support\Facades\Password;

class SellerApplicationController extends Controller
{
    public function store(StoreSellerApplicationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Normalize: application_type default to business
        $applicationType = $data['application_type'] ?? 'business';

        // If one_time and business_name not provided, use contact_name as fallback
        $businessName = $data['business_name'] ?? null;
        if (empty($businessName) && $applicationType === 'one_time') {
            $businessName = $data['contact_name'] ?? null;
        }

        try {
            $app = SellerApplication::create([
                'application_type' => $applicationType,
                'business_name' => $businessName,
                'contact_name' => $data['contact_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'message' => $data['message'] ?? null,
                'logo_url' => $data['logo_url'] ?? ($data['logo'] ?? null),
                'items' => $data['items'] ?? null,
                'status' => 'pending',
            ]);

            NotifyAdminOfSellerApplication::dispatch($app);

            return response()->json([
                'id' => $app->id,
                'status' => $app->status,
                'message' => 'Application received. We will review and respond.'
            ], 201);
        } catch (\Throwable $e) {
            Log::error('SellerApplicationController@store error: '.$e->getMessage(), ['payload' => $data, 'exception' => $e]);

            return response()->json(['message' => 'Failed to create application'], 500);
        }
    }

    // public function approve(Request $request, $id)
    // {
    //     $app = SellerApplication::find($id);
    //     if (!$app) {
    //         return response()->json(['message' => 'Application not found'], 404);
    //     }

    //     if ($app->status === 'approved') {
    //         return response()->json(['message' => 'Already approved'], 400);
    //     }

    //     // decide seller_type: keep what applicant requested, default 'one_time' or 'business'
    //     $sellerType = $app->application_type ?? 'one_time';

    //     // create or find user by email
    //     if (!$app->email) {
    //         return response()->json(['message' => 'Application missing email'], 422);
    //     }

    //     $user = User::where('email', $app->email)->first();

    //     if (!$user) {
    //         // Create a new user with a random password (they will set with the link)
    //         $user = User::create([
    //             'name' => $app->business_name ?? $app->contact_name ?? $app->email,
    //             'email' => $app->email,
    //             // temporary password (we don't expose) - will be overridden by reset flow
    //             'password' => Hash::make(Str::random(24)),
    //             'is_seller' => true,
    //             'seller_type' => $sellerType,
    //             'seller_application_id' => $app->id,
    //         ]);
    //     } else {
    //         // Existing user: mark as seller
    //         $user->update([
    //             'is_seller' => true,
    //             'seller_type' => $sellerType,
    //             'seller_application_id' => $app->id,
    //         ]);
    //     }

    //     // mark application as approved
    //     $app->status = 'approved';
    //     $app->save();

    //     // create password reset token for this user (so they can set password)
    //     /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
    //     $broker = Password::broker(); // default broker
    //     $token = $broker->createToken($user);

    //     // Build a frontend URL for setting password. Set this env var in your .env:
    //     // NEXT_PUBLIC_APP_URL=https://app.example.com
    //     $frontend = rtrim(env('NEXT_PUBLIC_APP_URL', config('app.url')), '/');
    //     $setPasswordUrl = $frontend . '/seller/set-password?token=' . urlencode($token) . '&email=' . urlencode($user->email);

    //     // send mail (queue or sync)
    //     try {
    //         Mail::to($user->email)->send(new SellerApprovedMail($user, $setPasswordUrl, $sellerType));
    //     } catch (\Throwable $e) {
    //         Log::error('Failed to send seller approved mail: '.$e->getMessage(), ['user_id' => $user->id, 'application_id' => $app->id]);
    //         // Continue — we still return success but notify admin to retry mail if needed
    //     }

    //     // mark verified time for long_term sellers (optional)
    //     if ($sellerType === 'long_term') {
    //         $user->seller_verified_at = now();
    //         $user->save();
    //     }

    //     return response()->json([
    //         'message' => 'Application approved and invitation sent',
    //         'user_id' => $user->id,
    //         'application_id' => $app->id,
    //     ], 200);
    // }
}

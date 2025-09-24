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
        $request->validate([
            'notes' => 'nullable|string|max:2000',
            'notify_email' => 'sometimes|boolean',
            'create_user' => 'sometimes|boolean'
        ]);

        $notify = (bool) $request->input('notify_email', true);
        $notes = $request->input('notes', null);
        $createUser = (bool) $request->input('create_user', true);

        $application = SellerApplication::find($id);
        if (! $application) {
            return response()->json(['message' => "Seller application not found: {$id}"], 404);
        }

        if ($application->status === 'approved') {
            return response()->json(['message' => 'Application already approved', 'application' => $application], 400);
        }

        DB::beginTransaction();
        $createdUser = null;
        try {
            $seller = Seller::create([
                'business_name' => $application->business_name,
                'contact_name'  => $application->contact_name,
                // map email field consistently
                'contact_email' => $application->email ?? $application->contact_email ?? null,
                'phone'         => $application->phone,
                'website'       => $application->website,
                // use logo_url (migration)
                'logo_path'     => $application->logo_url ?? $application->logo_path ?? null,
                'message'       => $application->message,
                'approved'      => true,
                'notes'         => $notes,
            ]);

            if ($createUser && !empty($application->email)) {
                $user = User::where('email', $application->email)->first();
                if (! $user) {
                    $tempPassword = Str::random(12);
                    $user = User::create([
                        'name' => $application->contact_name ?: $application->business_name,
                        'email' => $application->email,
                        'password' => bcrypt($tempPassword),
                        'is_seller' => true,
                    ]);
                    $createdUser = $user;
                }

                if (Schema::hasColumn('sellers', 'user_id')) {
                    $seller->user_id = $user->id;
                    $seller->save();
                }

                // non-blocking password reset email
                try {
                    Password::sendResetLink(['email' => $user->email]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send password reset to seller user: '.$e->getMessage());
                }
            }

            $application->status = 'approved';
            $application->notes = $notes;
            $application->approved_at = now();
            $application->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Approve application failed: '.$e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Failed to approve application'], 500);
        }

        if ($notify && !empty($seller->contact_email)) {
            try {
                Mail::to($seller->contact_email)->queue(new SellerApprovedMail($seller, $notes ?? ''));
            } catch (\Throwable $e) {
                Log::error('Failed to queue SellerApprovedMail: '.$e->getMessage());
            }
        }

        // optional: notify admin
        try {
            $adminEmail = config('mail.admin_email') ?? env('MAIL_ADMIN_EMAIL');
            if ($adminEmail && class_exists(\App\Mail\NewSellerCreatedToAdmin::class)) {
                Mail::to($adminEmail)->queue(new \App\Mail\NewSellerCreatedToAdmin($seller));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to queue NewSellerCreatedToAdmin: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Application approved and seller created',
            'seller' => $seller,
            'user_created' => $createdUser ? ['id' => $createdUser->id, 'email' => $createdUser->email] : null,
            'application' => $application->fresh(),
        ], 200);
    }
}

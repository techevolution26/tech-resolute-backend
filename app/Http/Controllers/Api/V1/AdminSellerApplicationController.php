<?php
// app/Http/Controllers/Api/V1/AdminSellerApplicationController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SellerApplication;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SellerApproved; // make a mailable or job
use App\Mail\SellerApprovedMail;

class AdminSellerApplicationController extends Controller
{
    public function index()
    {
        $apps = SellerApplication::orderBy('created_at','desc')->get();
        return response()->json($apps);
    }

    public function show($id)
    {
        $app = SellerApplication::findOrFail($id);
        return response()->json($app);
    }

    public function approve(Request $request, $id)
    {
        $app = SellerApplication::findOrFail($id);

        if ($app->status === 'approved') {
            return response()->json(['message' => 'Already approved'], 400);
        }

        // create or find a user for this seller
        $email = $app->email;
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $app->business_name ?? $app->contact_name ?? $email,
                // set a random password — force reset, or use passwordless flow
                'password' => Hash::make(Str::random(16)),
                'is_seller' => true,
            ]
        );

        // mark application approved & link user
        $app->status = 'approved';
        $app->approved_by = $request->user()->id ?? null;
        $app->seller_user_id = $user->id;
        $app->save();

        // optionally send email (queued)
        try {
            Mail::to($user->email)->queue(new SellerApprovedMail($user, $app));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue seller approved email: '.$e->getMessage());
        }

        return response()->json(['message' => 'Application approved', 'user_id' => $user->id]);
    }

    public function reject(Request $request, $id)
    {
        $app = SellerApplication::findOrFail($id);
        $app->status = 'rejected';
        $app->save();
        // optionally notify applicant
        return response()->json(['message' => 'Application rejected']);
    }
}

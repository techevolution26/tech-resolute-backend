<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! (bool) ($user->is_admin ?? false)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden — admin access required.'], 403);
            }
            // fallback for web uses
            return redirect()->route('login');
        }
        return $next($request);
    }
}

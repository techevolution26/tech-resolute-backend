<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdminOrSeller
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || (!($user->is_admin ?? false) && !($user->is_seller ?? false))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden — admin or seller access required.'], 403);
            }
            return redirect()->route('login');
        }
        return $next($request);
    }
}

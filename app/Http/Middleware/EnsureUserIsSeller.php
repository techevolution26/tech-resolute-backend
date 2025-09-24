<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsSeller
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! (bool) ($user->is_seller ?? false)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden — seller access required.'], 403);
            }
            return redirect()->route('login');
        }
        return $next($request);
    }
}

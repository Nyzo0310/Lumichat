<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (!$u || !$u->canAccessAdmin()) {
            return redirect()->route('chat.index')->with('status', 'not-authorized');
        }
        return $next($request);
    }
}

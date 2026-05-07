<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve tenant set in SetEstateManagerFromUrl
        $estate = app('estateManager'); 

        $user = $request->user();

        $user_type_id = (int)$user->user_type_id;

        if ($user_type_id || (int)$user->estate_manager_id !== (int)$estate->id) {
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        // Make Tenant user globally available if needed
        app()->instance('tenant', $user);

        return $next($request);
    }
}

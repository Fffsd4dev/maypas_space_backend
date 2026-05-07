<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve tenant set in SetEstateManagerFromUrl
        $estate = app('estateManager'); 

        $user = $request->user();

        $user_type_id = (int)$user->user_type_id;

        // Check if user is has a user_type column which is only present in the landlord_agents table
        if (!$user_type_id || (int)$user->estate_manager_id !== (int)$estate->id) {
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        // Make landlord user globally available if needed
        app()->instance('landlord', $user);

        return $next($request);
    }
}

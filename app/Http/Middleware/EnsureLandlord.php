<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLandlord
{
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve tenant set in SetEstateManagerFromUrl
        $estate = app('estateManager'); 

        $user = $request->user();
    
        $user_type_id = (int)$user->user_type_id;
        $user->estate_manager_id = (int)$estate->id;
        //typecast once to reduce processing time
       
        if (!$request->user()->tokenCan('landlord') || !in_array($user_type_id, [1, 2]) || $user->estate_manager_id !== $estate->id) {
        
            return response()->json(['message' => 'You are not authorized'], 403);
        }

        // Make landlord user globally available if needed
        app()->instance('landlord', $user);

        return $next($request);
    }
}

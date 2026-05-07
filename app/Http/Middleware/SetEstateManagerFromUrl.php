<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\EstateManager;

class SetEstateManagerFromUrl
{
    public function handle($request, Closure $next)
    {
        // Extract the tenant slug from the URL (e.g., example.com/{tenant}/api)
        $estateSlug = $request->route('tenant_slug');

        $estate = EstateManager::where('slug', $estateSlug)->first();

        if (!$estate) {
            return response()->json(['message' => 'This Estate is not registered on our platform'], 404);
        }

        // Set the estate Manager globally
        app()->instance('estateManager', $estate);


        return $next($request);
    }
}

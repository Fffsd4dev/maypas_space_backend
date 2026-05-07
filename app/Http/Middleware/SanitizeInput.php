<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    /**
     * Keys to skip sanitizing.
     */
    private array $skip = ['password', 'password_confirmation'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $sanitized = $this->sanitizeArray($request->all());

        // Replace request data with sanitized version
        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Sanitize an array of inputs.
     */
    private function sanitizeArray(array $inputs): array
    {
        foreach ($inputs as $key => $value) {
            if (in_array($key, $this->skip, true)) {
                // Skip sanitizing for these keys
                continue;
            }

            if (is_string($value)) {
                // Trim whitespace and remove HTML tags
                $inputs[$key] = strip_tags(trim($value));
            } elseif (is_array($value)) {
                // Recursively sanitize arrays
                $inputs[$key] = $this->sanitizeArray($value);
            }
        }
        return $inputs;
    }
}

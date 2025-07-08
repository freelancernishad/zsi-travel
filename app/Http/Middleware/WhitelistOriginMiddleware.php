<?php

namespace App\Http\Middleware;

use App\Models\AllowedOrigin;
use Closure;

class WhitelistOriginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {


        $allowedAllOrigin = AllowedOrigin::where('origin_url', '*')->exists();
        if($allowedAllOrigin){
            return $next($request);
        }


        // Get the 'Origin' header from the request
        $origin = $request->header('Origin');

        // If the origin is empty, check if there is a wildcard (empty string) in the allowed origins
        if ($origin === '' || $origin === null) {
            // Check if there's an empty string '' in the allowed origins
            $allowedOrigin = AllowedOrigin::where('origin_url', 'postman')->exists();

            // If empty origin is not allowed in the database, return a 403 response
            if (!$allowedOrigin) {
                return response()->json([
                    'message' => 'Access denied. Empty origin is not allowed.',
                ], 403);
            }
        } else {
            // Check if the origin exists in the database for non-empty origins
            $allowedOrigin = AllowedOrigin::where('origin_url', $origin)->exists();

            // If the origin is not allowed, return a 403 response
            if (!$allowedOrigin) {
                return response()->json([
                    'message' => 'Access denied. Your origin is not allowed.',
                ], 403);
            }
        }

        // If the origin is allowed, proceed with the request
        return $next($request);
    }
}

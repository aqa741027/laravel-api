<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCloudFrontHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->environment('production')) {
            return $next($request);
        }

        $expected = config('services.cloudfront.secret_header');
        $actual = $request->header('X-From-CloudFront');

        if (!$expected || !$actual || !hash_equals($expected, $actual)) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return $next($request);
    }
}

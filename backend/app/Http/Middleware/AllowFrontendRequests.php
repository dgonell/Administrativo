<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowFrontendRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204, $this->headers());
        }

        $response = $next($request);

        foreach ($this->headers() as $header => $value) {
            $response->headers->set($header, $value);
        }

        return $response;
    }

    private function headers(): array
    {
        $allowedOrigins = collect(explode(',', env('FRONTEND_URLS', env('FRONTEND_URL', 'http://localhost:5173'))))
            ->map(fn (string $origin) => rtrim(trim($origin), '/'))
            ->filter()
            ->values();
        $requestOrigin = rtrim((string) request()->headers->get('Origin'), '/');
        $allowOrigin = $allowedOrigins->contains($requestOrigin)
            ? $requestOrigin
            : $allowedOrigins->first();

        return [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept',
            'Access-Control-Allow-Credentials' => 'true',
        ];
    }
}

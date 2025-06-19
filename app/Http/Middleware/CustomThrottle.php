<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CustomThrottle
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->limiter->remaining($key, $maxAttempts),
            $this->calculateRemainingSeconds($key, $maxAttempts)
        );
    }

    protected function resolveRequestSignature($request)
    {
        // Use the Authorization token as the key
        return sha1($request->header('Authorization', 'default') . '|' . $request->path());
    }

    protected function buildResponse($key, $maxAttempts)
    {
        $response = new Response(
            json_encode(['message' => 'Too many attempts.']),
            429,
            ['Content-Type' => 'application/json']
        );

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->limiter->remaining($key, $maxAttempts),
            $this->limiter->retriesLeft($key, $maxAttempts)
        );
    }

    protected function addHeaders($response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        if ($retryAfter !== null) {
            $response->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => $this->limiter->availableAt($key),
            ]);
        }

        return $response;
    }

    protected function calculateRemainingSeconds($key, $maxAttempts)
    {
        return $this->limiter->availableIn($key);
    }
}
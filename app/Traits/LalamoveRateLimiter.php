<?php

namespace App\Traits;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

trait LalamoveRateLimiter
{

    public function checkLalamoveRateLimit()
    {
        $key = 'lalamove:qpm'; // You can customize this per user or per API key if needed
    
        $executed = RateLimiter::attempt(
            $key,
            $maxAttempts = 30, // max 30 attempts
            function () {
                // Do nothing; we just want to allow the request
            },
            $decaySeconds = 60 // per minute
        );
    
        if (! $executed) {
            $availableIn = RateLimiter::availableIn($key);
            \Log::warning("⛔️ Lalamove QPM limit exceeded. Try again in {$availableIn} seconds.");
    
            throw new \Exception("Rate limit exceeded. Try again in {$availableIn} seconds.");
        }
    }
    
}
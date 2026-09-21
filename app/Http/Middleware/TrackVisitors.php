<?php

namespace App\Http\Middleware;

use App\Services\VisitorTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts page views of the public website. The work happens in terminate(),
 * after the response has been handed to the visitor, so pages stay fast.
 */
class TrackVisitors
{
    public function __construct(protected VisitorTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($this->tracker->shouldTrack($request, $response)) {
            $this->tracker->track($request, $response);
        }
    }
}

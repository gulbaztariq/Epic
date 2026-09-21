<?php

namespace App\Http\Middleware;

use App\Services\WebScheduler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the site's recurring work ticking over on hosting without cron.
 *
 * Like visitor tracking, this happens in terminate() — after the visitor has
 * their page — and it checks for due work only every few minutes.
 */
class RunDueScheduledTasks
{
    public function __construct(protected WebScheduler $scheduler) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->scheduler->isEnabled() || ! $this->scheduler->shouldCheck()) {
                return;
            }

            $this->scheduler->runDue();
        } catch (\Throwable $e) {
            // Housekeeping must never affect a page.
            Log::warning('Web scheduler failed: '.$e->getMessage());
        }
    }
}

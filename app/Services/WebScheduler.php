<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Runs the site's recurring work without a cron job.
 *
 * Shared hosting plans do not always offer cron. When they don't, this runs the
 * due tasks after a page response (see the RunDueScheduledTasks middleware), so
 * visitor locations are still resolved and old records still tidied away.
 *
 * It is safe to leave enabled alongside a real cron job: each task records when
 * it last ran, so it never runs twice inside its own interval.
 */
class WebScheduler
{
    /** How often to look for due work, in seconds. */
    public const CHECK_EVERY = 300;

    /**
     * @var array<string, array{command: string, options: array<string, mixed>, every: int, label: string}>
     */
    public const TASKS = [
        'visitor-locations' => [
            'command' => 'epic:resolve-visitor-locations',
            'options' => ['--limit' => 50],
            'every' => 900,
            'label' => 'Look up visitor locations',
        ],
        'prune-visits' => [
            'command' => 'epic:prune-visits',
            'options' => [],
            'every' => 86400,
            'label' => 'Remove expired visitor records',
        ],
    ];

    public function isEnabled(): bool
    {
        return setting('web_scheduler_enabled', '1') === '1';
    }

    /**
     * A single cheap cache read that keeps this off the hot path: we only look
     * for due work every few minutes, whatever the traffic.
     */
    public function shouldCheck(): bool
    {
        return (int) Cache::get('epic.scheduler.next_check', 0) <= now()->timestamp;
    }

    /**
     * Run whichever tasks are due.
     *
     * @return array<int, array{key: string, label: string, output: string}>
     */
    public function runDue(bool $force = false): array
    {
        Cache::put('epic.scheduler.next_check', now()->addSeconds(self::CHECK_EVERY)->timestamp, 3600);

        // Never let two requests run the same task at once.
        if (! Cache::add('epic.scheduler.lock', 1, 120)) {
            return [];
        }

        $ran = [];

        try {
            foreach (self::TASKS as $key => $task) {
                if (! $force && ! $this->isDue($key, $task['every'])) {
                    continue;
                }

                try {
                    Artisan::call($task['command'], $task['options']);
                    $ran[] = [
                        'key' => $key,
                        'label' => $task['label'],
                        'output' => trim(Artisan::output()),
                    ];
                } catch (\Throwable $e) {
                    Log::warning('Scheduled task '.$task['command'].' failed: '.$e->getMessage());
                } finally {
                    Cache::forever($this->stampKey($key), now()->timestamp);
                }
            }
        } finally {
            Cache::forget('epic.scheduler.lock');
        }

        return $ran;
    }

    public function isDue(string $key, int $every): bool
    {
        $last = (int) Cache::get($this->stampKey($key), 0);

        return $last === 0 || ($now = now()->timestamp) - $last >= $every || $last > $now;
    }

    public function lastRunAt(string $key): ?Carbon
    {
        $stamp = (int) Cache::get($this->stampKey($key), 0);

        return $stamp > 0 ? Carbon::createFromTimestamp($stamp) : null;
    }

    /** When each task last ran, for the dashboard. */
    public function status(): array
    {
        $status = [];

        foreach (self::TASKS as $key => $task) {
            $status[$key] = [
                'label' => $task['label'],
                'last_run' => $this->lastRunAt($key),
                'every' => $task['every'],
            ];
        }

        return $status;
    }

    protected function stampKey(string $key): string
    {
        return 'epic.scheduler.last.'.$key;
    }
}

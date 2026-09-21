<?php

namespace App\Services;

use App\Models\IpLocation;
use App\Models\Visit;
use App\Support\ReportRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every figure on the analytics screens, measured over one ReportRange.
 */
class VisitorReport
{
    public function __construct(protected ReportRange $range) {}

    public function range(): ReportRange
    {
        return $this->range;
    }

    /** Visits inside the range, with crawlers excluded unless asked for. */
    public function query(?ReportRange $range = null): Builder
    {
        $range ??= $this->range;

        return Visit::query()
            ->between($range->from, $range->to)
            ->when(! $range->includeBots, fn (Builder $q) => $q->humans());
    }

    /**
     * Headline numbers, each with the change against the previous period.
     *
     * @return array<string, array{value: int, previous: int, change: ?float}>
     */
    public function totals(): array
    {
        $current = $this->measure($this->range);
        $previous = $this->range->preset === 'all'
            ? array_fill_keys(array_keys($current), 0)
            : $this->measure($this->range->previous());

        $totals = [];

        foreach ($current as $key => $value) {
            $was = $previous[$key] ?? 0;

            $totals[$key] = [
                'value' => $value,
                'previous' => $was,
                'change' => $was > 0 ? round((($value - $was) / $was) * 100, 1) : null,
            ];
        }

        return $totals;
    }

    /** @return array<string, int> */
    protected function measure(ReportRange $range): array
    {
        $base = fn () => $this->query($range);

        return [
            'page_views' => (clone $base())->count(),
            'visitors' => (clone $base())->distinct('visitor_key')->count('visitor_key'),
            'sessions' => (clone $base())->distinct('session_key')->count('session_key'),
            'new_visitors' => (clone $base())->where('is_new_visitor', true)->count(),
            'countries' => (clone $base())->whereNotNull('country_code')->distinct('country_code')->count('country_code'),
            'bots' => Visit::between($range->from, $range->to)->bots()->count(),
        ];
    }

    /**
     * Page views and visitors per day (or per hour for a single day), with
     * empty buckets filled in so the chart has a continuous axis.
     *
     * @return array<int, array{key: string, label: string, short: string, views: int, visitors: int}>
     */
    public function timeline(): array
    {
        return $this->range->isSingleDay() ? $this->hourlyTimeline() : $this->dailyTimeline();
    }

    protected function dailyTimeline(): array
    {
        $views = $this->query()
            ->selectRaw('DATE(visited_at) as bucket, COUNT(*) as views')
            ->groupBy(DB::raw('DATE(visited_at)'))
            ->pluck('views', 'bucket');

        $visitors = $this->query()
            ->selectRaw('DATE(visited_at) as bucket, COUNT(DISTINCT visitor_key) as visitors')
            ->groupBy(DB::raw('DATE(visited_at)'))
            ->pluck('visitors', 'bucket');

        $start = $this->timelineStart();

        $timeline = [];
        $cursor = $start->copy();
        $guard = 0;

        while ($cursor->lte($this->range->to) && $guard++ < 800) {
            $key = $cursor->toDateString();

            $timeline[] = [
                'key' => $key,
                'label' => $cursor->format('d M Y'),
                'short' => $cursor->format('j M'),
                'views' => (int) ($views[$key] ?? 0),
                'visitors' => (int) ($visitors[$key] ?? 0),
            ];

            $cursor->addDay();
        }

        return $timeline;
    }

    /** "All time" starts at the first recorded visit rather than the epoch. */
    protected function timelineStart(): Carbon
    {
        if ($this->range->preset !== 'all') {
            return $this->range->from->copy()->startOfDay();
        }

        $first = Visit::min('visited_at');

        return $first
            ? Carbon::parse($first)->startOfDay()
            : $this->range->to->copy()->startOfDay();
    }

    protected function hourlyTimeline(): array
    {
        $expression = match (DB::getDriverName()) {
            'sqlite' => "CAST(strftime('%H', visited_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(HOUR FROM visited_at)',
            default => 'HOUR(visited_at)',
        };

        $views = $this->query()
            ->selectRaw("$expression as bucket, COUNT(*) as views")
            ->groupBy(DB::raw($expression))
            ->pluck('views', 'bucket');

        $visitors = $this->query()
            ->selectRaw("$expression as bucket, COUNT(DISTINCT visitor_key) as visitors")
            ->groupBy(DB::raw($expression))
            ->pluck('visitors', 'bucket');

        $timeline = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $timeline[] = [
                'key' => (string) $hour,
                'label' => sprintf('%02d:00', $hour),
                'short' => $hour % 3 === 0 ? sprintf('%02d', $hour) : '',
                'views' => (int) ($views[$hour] ?? 0),
                'visitors' => (int) ($visitors[$hour] ?? 0),
            ];
        }

        return $timeline;
    }

    /* ------------------------------------------------------------- Breakdowns */

    public function topPages(int $limit = 10): Collection
    {
        return $this->query()
            ->selectRaw('path, MAX(page_title) as page_title, COUNT(*) as views, COUNT(DISTINCT visitor_key) as visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public function topCountries(int $limit = 10): Collection
    {
        return $this->query()
            ->whereNotNull('country_code')
            ->selectRaw('country_code, MAX(country) as country, COUNT(*) as views, COUNT(DISTINCT visitor_key) as visitors')
            ->groupBy('country_code')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public function topCities(int $limit = 10): Collection
    {
        return $this->query()
            ->whereNotNull('city')
            ->selectRaw('city, MAX(country) as country, COUNT(*) as views, COUNT(DISTINCT visitor_key) as visitors')
            ->groupBy('city')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public function breakdown(string $column, int $limit = 8): Collection
    {
        return $this->query()
            ->whereNotNull($column)
            ->selectRaw("$column as label, COUNT(*) as views")
            ->groupBy($column)
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public function referrers(int $limit = 10): Collection
    {
        return $this->query()
            ->whereNotNull('referrer_host')
            ->selectRaw('referrer_host as label, COUNT(*) as views, COUNT(DISTINCT visitor_key) as visitors')
            ->groupBy('referrer_host')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    public function recentVisits(int $limit = 12): Collection
    {
        return $this->query()->orderByDesc('visited_at')->limit($limit)->get();
    }

    /** How many addresses are still waiting for a location lookup. */
    public function pendingLocations(): int
    {
        return IpLocation::pending()->count();
    }
}

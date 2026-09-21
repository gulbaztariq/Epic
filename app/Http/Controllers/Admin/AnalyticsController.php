<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\GeoLocator;
use App\Services\VisitorReport;
use App\Support\Chart;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    /** Chart colours — the EPIC blue and green, validated for colour-blind separation. */
    public const SERIES = [
        ['key' => 'views', 'label' => 'Page views', 'color' => '#0f6fc0'],
        ['key' => 'visitors', 'label' => 'Visitors', 'color' => '#41a62a'],
    ];

    public function index(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $report = new VisitorReport($range);
        $timeline = $report->timeline();

        return view('admin.analytics.overview', [
            'range' => $range,
            'totals' => $report->totals(),
            'chart' => Chart::timeSeries($timeline, self::SERIES),
            'series' => self::SERIES,
            'topPages' => $report->topPages(),
            'topCountries' => $report->topCountries(),
            'topCities' => $report->topCities(),
            'devices' => $report->breakdown('device_type'),
            'browsers' => $report->breakdown('browser'),
            'platforms' => $report->breakdown('platform'),
            'referrers' => $report->referrers(),
            'recent' => $report->recentVisits(8),
            'pendingLocations' => $report->pendingLocations(),
            'hasAnyVisit' => Visit::exists(),
        ]);
    }

    public function visitors(Request $request)
    {
        $range = ReportRange::fromRequest($request);
        $report = new VisitorReport($range);

        $visits = $report->query()
            ->when($request->query('country'), fn ($q, $code) => $q->where('country_code', $code))
            ->when($request->query('device'), fn ($q, $device) => $q->where('device_type', $device))
            ->when($request->query('path'), fn ($q, $path) => $q->where('path', 'like', '%'.$path.'%'))
            ->orderByDesc('visited_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.analytics.visitors', [
            'range' => $range,
            'visits' => $visits,
            'countries' => $report->topCountries(100)->pluck('country', 'country_code')->filter(),
            'deviceTypes' => Visit::DEVICE_TYPES,
        ]);
    }

    /**
     * Download the selected date range: either the day-by-day summary or the
     * full visitor log.
     */
    public function export(Request $request): StreamedResponse
    {
        $range = ReportRange::fromRequest($request);
        $report = new VisitorReport($range);
        $type = $request->query('type') === 'visits' ? 'visits' : 'daily';

        $filename = sprintf(
            'epic-%s-%s-to-%s.csv',
            $type === 'visits' ? 'visitor-log' : 'visitor-summary',
            $range->from->toDateString(),
            $range->to->toDateString()
        );

        return response()->streamDownload(function () use ($report, $range, $type) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['EPIC website report']);
            fputcsv($handle, ['Period', $range->subtitle()]);
            fputcsv($handle, ['Crawlers included', $range->includeBots ? 'Yes' : 'No']);
            fputcsv($handle, ['Generated', now()->format('d M Y H:i')]);
            fputcsv($handle, []);

            if ($type === 'daily') {
                fputcsv($handle, ['Date', 'Page views', 'Visitors']);

                foreach ($report->timeline() as $row) {
                    fputcsv($handle, [$row['label'], $row['views'], $row['visitors']]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['Top pages']);
                fputcsv($handle, ['Path', 'Title', 'Page views', 'Visitors']);

                foreach ($report->topPages(50) as $page) {
                    fputcsv($handle, [$page->path, $page->page_title, $page->views, $page->visitors]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['Top countries']);
                fputcsv($handle, ['Country', 'Code', 'Page views', 'Visitors']);

                foreach ($report->topCountries(100) as $country) {
                    fputcsv($handle, [$country->country, $country->country_code, $country->views, $country->visitors]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['Top cities']);
                fputcsv($handle, ['City', 'Country', 'Page views', 'Visitors']);

                foreach ($report->topCities(100) as $city) {
                    fputcsv($handle, [$city->city, $city->country, $city->views, $city->visitors]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['Referrers']);
                fputcsv($handle, ['Source', 'Page views', 'Visitors']);

                foreach ($report->referrers(100) as $referrer) {
                    fputcsv($handle, [$referrer->label, $referrer->views, $referrer->visitors]);
                }
            } else {
                fputcsv($handle, ['Date and time', 'Page', 'Title', 'Country', 'Region', 'City', 'Device', 'Browser', 'Platform', 'Referrer', 'IP address', 'Crawler']);

                $report->query()->orderBy('visited_at')->chunk(500, function ($chunk) use ($handle) {
                    foreach ($chunk as $visit) {
                        fputcsv($handle, [
                            $visit->visited_at?->format('Y-m-d H:i:s'),
                            $visit->path,
                            $visit->page_title,
                            $visit->country,
                            $visit->region,
                            $visit->city,
                            $visit->device_label,
                            $visit->browser,
                            $visit->platform,
                            $visit->referrer_host,
                            $visit->ip_address,
                            $visit->is_bot ? 'Yes' : 'No',
                        ]);
                    }
                });
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Look up pending visitor locations straight away, for sites without cron. */
    public function resolve(Request $request, GeoLocator $locator)
    {
        $result = $locator->resolvePending(100);

        $message = $result['resolved'] > 0
            ? "Located {$result['resolved']} address(es) and updated {$result['visits']} visit(s)."
            : 'No new locations could be resolved right now.';

        return back()->with($result['resolved'] > 0 ? 'success' : 'info', $message);
    }
}

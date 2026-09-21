@extends('layouts.admin')

@section('title', 'Visitor analytics')
@section('heading', 'Visitor analytics')
@section('crumb', 'Who visits the website, from where, and what they read')

@section('content')
    <div class="page-head no-print">
        <div>
            <h2 style="margin:0">{{ $range->label() }}</h2>
            <p>{{ $range->subtitle() }}</p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-outline" href="{{ route('admin.analytics.visitors', $range->query()) }}">{!! icon('list') !!} Visitor log</a>
            <a class="btn btn-outline" href="{{ route('admin.analytics.export', $range->query(['type' => 'daily'])) }}">{!! icon('download') !!} Summary CSV</a>
            <a class="btn btn-outline" href="{{ route('admin.analytics.export', $range->query(['type' => 'visits'])) }}">{!! icon('download') !!} Full log CSV</a>
            <button class="btn btn-primary" type="button" onclick="window.print()">{!! icon('document') !!} Print report</button>
        </div>
    </div>

    <div class="print-only print-head">
        <h1>{{ setting('site_name_full', 'Economic Policy and Innovation Centre (EPIC)') }}</h1>
        <p>Website visitor report · {{ $range->subtitle() }} · generated {{ now()->format('d M Y H:i') }}</p>
    </div>

    @if (setting('analytics_enabled', '1') !== '1')
        <div class="alert alert-warn no-print">
            {!! icon('close') !!}
            <div>Visitor counting is switched off, so no new visits are being recorded.
                <a href="{{ route('admin.settings', 'analytics') }}">Turn it on in Site settings → Analytics</a>.</div>
        </div>
    @endif

    @include('admin.analytics.partials.range-filter', ['routeName' => 'admin.analytics.index'])

    @if (! $hasAnyVisit)
        <div class="alert alert-info no-print">
            {!! icon('sparkle') !!}
            <div>No visits have been recorded yet. Open the website in a browser and this page will start filling in.</div>
        </div>
    @endif

    {{-- Headline numbers --}}
    <div class="stat-row">
        @php
            $tiles = [
                'page_views' => ['label' => 'Page views', 'icon' => 'eye'],
                'visitors' => ['label' => 'Visitors', 'icon' => 'people'],
                'sessions' => ['label' => 'Sessions', 'icon' => 'clock'],
                'new_visitors' => ['label' => 'First-time visits', 'icon' => 'sparkle'],
                'countries' => ['label' => 'Countries', 'icon' => 'globe'],
            ];
        @endphp

        @foreach ($tiles as $key => $meta)
            @php($figure = $totals[$key])
            <div class="stat">
                <span class="stat-label">{{ $meta['label'] }}</span>
                <strong class="stat-value">{{ number_format($figure['value']) }}</strong>

                @if ($figure['change'] !== null)
                    <span class="stat-delta {{ $figure['change'] > 0 ? 'is-up' : ($figure['change'] < 0 ? 'is-down' : '') }}">
                        {{ $figure['change'] > 0 ? '+' : '' }}{{ $figure['change'] }}%
                        <small>{{ $range->comparisonLabel() }}</small>
                    </span>
                @else
                    <span class="stat-delta"><small>{{ $range->preset === 'all' ? 'all recorded visits' : 'no earlier data to compare' }}</small></span>
                @endif
            </div>
        @endforeach

        <div class="stat">
            <span class="stat-label">Crawlers &amp; bots</span>
            <strong class="stat-value">{{ number_format($totals['bots']['value']) }}</strong>
            <span class="stat-delta"><small>{{ $range->includeBots ? 'included in the figures above' : 'excluded from the figures above' }}</small></span>
        </div>
    </div>

    {{-- Trend --}}
    @include('admin.analytics.partials.line-chart', ['chart' => $chart, 'range' => $range, 'title' => 'Page views and visitors'])

    {{-- Content and geography --}}
    <div class="grid grid-2" style="margin-top:20px">
        @include('admin.analytics.partials.bar-list', [
            'title' => 'Most read pages',
            'rows' => $topPages,
            'labelKey' => 'path',
            'subKey' => 'page_title',
            'valueKey' => 'views',
            'icon' => 'document',
            'emptyText' => 'No page views in this period.',
        ])

        @include('admin.analytics.partials.bar-list', [
            'title' => 'Countries',
            'rows' => $topCountries,
            'labelKey' => 'country',
            'subKey' => 'country_code',
            'valueKey' => 'views',
            'icon' => 'globe',
            'emptyText' => 'No locations resolved yet.',
        ])
    </div>

    <div class="grid grid-2" style="margin-top:20px">
        @include('admin.analytics.partials.bar-list', [
            'title' => 'Cities',
            'rows' => $topCities,
            'labelKey' => 'city',
            'subKey' => 'country',
            'valueKey' => 'views',
            'icon' => 'location',
            'emptyText' => 'No cities resolved yet.',
        ])

        @include('admin.analytics.partials.bar-list', [
            'title' => 'Traffic sources',
            'rows' => $referrers,
            'labelKey' => 'label',
            'valueKey' => 'views',
            'icon' => 'network',
            'emptyText' => 'No external referrers in this period.',
        ])
    </div>

    <div class="grid grid-3" style="margin-top:20px">
        @include('admin.analytics.partials.bar-list', [
            'title' => 'Devices',
            'rows' => $devices->map(fn ($row) => (object) ['label' => \App\Models\Visit::DEVICE_TYPES[$row->label] ?? $row->label, 'views' => $row->views]),
            'labelKey' => 'label',
            'valueKey' => 'views',
            'icon' => 'monitor',
            'emptyText' => 'No device data yet.',
        ])

        @include('admin.analytics.partials.bar-list', [
            'title' => 'Browsers',
            'rows' => $browsers,
            'labelKey' => 'label',
            'valueKey' => 'views',
            'icon' => 'grid',
            'emptyText' => 'No browser data yet.',
        ])

        @include('admin.analytics.partials.bar-list', [
            'title' => 'Operating systems',
            'rows' => $platforms,
            'labelKey' => 'label',
            'valueKey' => 'views',
            'icon' => 'cpu',
            'emptyText' => 'No platform data yet.',
        ])
    </div>

    {{-- Latest arrivals --}}
    <div class="card" style="margin-top:20px">
        <div class="card-head">
            <h3>Latest visits</h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @if ($pendingLocations > 0)
                    <form action="{{ route('admin.analytics.resolve') }}" method="post" class="no-print">
                        @csrf
                        <button class="btn btn-outline btn-sm" type="submit" title="{{ $pendingLocations }} address(es) waiting for a location lookup">
                            {!! icon('globe') !!} Resolve {{ $pendingLocations }} location{{ $pendingLocations === 1 ? '' : 's' }}
                        </button>
                    </form>
                @endif
                <a class="btn btn-outline btn-sm" href="{{ route('admin.analytics.visitors', $range->query()) }}">View full log</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>When</th><th>Page</th><th>Location</th><th>Device</th><th>Source</th></tr>
                </thead>
                <tbody>
                    @forelse ($recent as $visit)
                        <tr>
                            <td style="white-space:nowrap">{{ $visit->visited_at?->format('d M, H:i') }}</td>
                            <td>
                                <span class="row-title">{{ $visit->path }}</span>
                                @if ($visit->page_title)<span class="row-sub">{{ Str::limit($visit->page_title, 52) }}</span>@endif
                            </td>
                            <td>{{ $visit->location_label }}</td>
                            <td>
                                {{ $visit->device_label }}
                                @if ($visit->browser)<span class="row-sub">{{ $visit->browser }}{{ $visit->platform ? ' · '.$visit->platform : '' }}</span>@endif
                            </td>
                            <td>{{ $visit->referrer_host ?: 'Direct' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty">{!! icon('people') !!}<p>No visits in this period.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="report-note">
        Figures cover {{ $range->subtitle() }}.
        {{ $range->includeBots ? 'Crawlers and monitoring tools are included.' : 'Crawlers and monitoring tools are excluded.' }}
        Locations come from the visitor's IP address and are accurate to city level at best.
    </p>
@endsection

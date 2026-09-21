@extends('layouts.admin')

@section('title', 'Visitor log')
@section('heading', 'Visitor log')
@section('crumb', 'Every recorded page view, newest first')

@section('content')
    <div class="page-head no-print">
        <div>
            <h2 style="margin:0">{{ number_format($visits->total()) }} page view{{ $visits->total() === 1 ? '' : 's' }}</h2>
            <p>{{ $range->subtitle() }}</p>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-outline" href="{{ route('admin.analytics.index', $range->query()) }}">{!! icon('chart') !!} Overview</a>
            <a class="btn btn-outline" href="{{ route('admin.analytics.export', $range->query(['type' => 'visits'])) }}">{!! icon('download') !!} Export CSV</a>
        </div>
    </div>

    @include('admin.analytics.partials.range-filter', ['routeName' => 'admin.analytics.visitors'])

    <div class="toolbar no-print">
        <form action="{{ route('admin.analytics.visitors') }}" method="get">
            @foreach (request()->only(['range', 'from', 'to', 'bots']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <input class="control" type="search" name="path" value="{{ request('path') }}" placeholder="Filter by page path…">

            <select class="control" name="country" onchange="this.form.submit()">
                <option value="">All countries</option>
                @foreach ($countries as $code => $name)
                    <option value="{{ $code }}" @selected(request('country') === $code)>{{ $name }}</option>
                @endforeach
            </select>

            <select class="control" name="device" onchange="this.form.submit()">
                <option value="">All devices</option>
                @foreach ($deviceTypes as $value => $label)
                    <option value="{{ $value }}" @selected(request('device') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn btn-outline" type="submit">{!! icon('search') !!} Filter</button>

            @if (request()->hasAny(['path', 'country', 'device']))
                <a class="btn btn-outline" href="{{ route('admin.analytics.visitors', $range->query()) }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Page</th>
                        <th>Location</th>
                        <th>Device &amp; browser</th>
                        <th>Source</th>
                        <th>IP address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td style="white-space:nowrap">
                                <span class="row-title">{{ $visit->visited_at?->format('d M Y') }}</span>
                                <span class="row-sub">{{ $visit->visited_at?->format('H:i:s') }}</span>
                            </td>
                            <td>
                                <span class="row-title">{{ $visit->path }}</span>
                                @if ($visit->page_title)<span class="row-sub">{{ Str::limit($visit->page_title, 60) }}</span>@endif
                            </td>
                            <td>
                                {{ $visit->location_label }}
                                @if ($visit->region)<span class="row-sub">{{ $visit->region }}</span>@endif
                            </td>
                            <td>
                                <span class="badge {{ $visit->is_bot ? 'badge-grey' : 'badge-blue' }}">{{ $visit->device_label }}</span>
                                <span class="row-sub">{{ collect([$visit->browser, $visit->platform])->filter()->implode(' · ') ?: '—' }}</span>
                            </td>
                            <td>
                                {{ $visit->referrer_host ?: 'Direct' }}
                                @if ($visit->is_new_visitor)<span class="row-sub">first visit</span>@endif
                            </td>
                            <td style="white-space:nowrap">{{ $visit->ip_address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty">{!! icon('people') !!}<p>No visits match these filters.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $visits->links('vendor.pagination.epic') }}
    </div>

    <p class="report-note">
        IP addresses are stored with the last part removed unless full addresses were
        enabled in <a href="{{ route('admin.settings', 'analytics') }}">Site settings → Analytics</a>.
    </p>
@endsection

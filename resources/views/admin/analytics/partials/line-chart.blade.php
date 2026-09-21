@php
    $chartId = 'chart-'.Str::random(6);
    $labelled = collect($chart['series'])->filter(fn ($s) => $s['last'] !== null)->values();
    // Only direct-label the line ends when they are far enough apart to read.
    $showEndLabels = $labelled->count() < 2
        || abs($labelled[0]['last']['y'] - $labelled[1]['last']['y']) >= 16;
@endphp

<div class="card chart-card">
    <div class="card-head">
        <div>
            <h3>{{ $title ?? 'Page views and visitors' }}</h3>
            <p class="chart-sub">{{ $range->subtitle() }}{{ $range->isSingleDay() ? ' · by hour' : ' · by day' }}</p>
        </div>

        <div class="chart-legend">
            @foreach ($chart['series'] as $line)
                <span><i style="background:{{ $line['color'] }}"></i>{{ $line['label'] }}</span>
            @endforeach
        </div>
    </div>

    <div class="chart-wrap" data-chart="{{ $chartId }}">
        <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}"
             role="img" aria-label="{{ $title ?? 'Page views and visitors' }} over {{ $range->subtitle() }}">
            {{-- Gridlines and y-axis --}}
            @foreach ($chart['yTicks'] as $tick)
                <line x1="{{ $chart['pad']['left'] }}" y1="{{ $tick['y'] }}"
                      x2="{{ $chart['width'] - $chart['pad']['right'] }}" y2="{{ $tick['y'] }}"
                      class="chart-grid" />
                <text x="{{ $chart['pad']['left'] - 10 }}" y="{{ $tick['y'] + 4 }}" class="chart-axis" text-anchor="end">{{ $tick['label'] }}</text>
            @endforeach

            {{-- x-axis --}}
            @foreach ($chart['xTicks'] as $tick)
                <text x="{{ $tick['x'] }}" y="{{ $chart['height'] - 10 }}" class="chart-axis" text-anchor="{{ $tick['anchor'] }}">{{ $tick['label'] }}</text>
            @endforeach

            {{-- Series --}}
            @foreach ($chart['series'] as $line)
                @if ($line['area'])
                    <path d="{{ $line['area'] }}" fill="{{ $line['color'] }}" fill-opacity="0.1" />
                @endif
                @if ($line['line'])
                    <path d="{{ $line['line'] }}" fill="none" stroke="{{ $line['color'] }}" stroke-width="2"
                          stroke-linejoin="round" stroke-linecap="round" />
                @endif
                @if ($line['last'])
                    <circle cx="{{ $line['last']['x'] }}" cy="{{ $line['last']['y'] }}" r="4.5"
                            fill="{{ $line['color'] }}" stroke="#ffffff" stroke-width="2" />
                    @if ($showEndLabels && ! $chart['isEmpty'])
                        <text x="{{ $line['last']['x'] - 10 }}" y="{{ $line['last']['y'] - 10 }}" class="chart-value" text-anchor="end">{{ number_format($line['last']['value']) }}</text>
                    @endif
                @endif
            @endforeach

            {{-- Hover layer --}}
            <line class="chart-crosshair" x1="0" y1="{{ $chart['pad']['top'] }}" x2="0" y2="{{ $chart['plot']['bottom'] }}" style="opacity:0" />
            @foreach ($chart['columns'] as $column)
                <rect class="chart-hit" x="{{ $column['x'] }}" y="{{ $chart['pad']['top'] }}"
                      width="{{ $column['width'] }}" height="{{ $chart['plot']['height'] }}"
                      data-index="{{ $column['index'] }}" data-centre="{{ $column['centre'] }}" />
            @endforeach
        </svg>

        <div class="chart-tooltip" hidden></div>

        @if ($chart['isEmpty'])
            <p class="chart-empty">No visits recorded in this period yet.</p>
        @endif
    </div>

    <script type="application/json" data-chart-points="{{ $chartId }}">@json(collect($chart['columns'])->map(fn ($c) => ['label' => $c['label'], 'values' => $c['values']])->all())</script>
</div>

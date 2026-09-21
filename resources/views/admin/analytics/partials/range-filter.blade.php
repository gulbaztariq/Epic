{{-- One filter row, above everything it scopes. Date range first. --}}
@php($routeName = $routeName ?? 'admin.analytics.index')

<div class="range-bar">
    <div class="range-presets">
        @foreach (\App\Support\ReportRange::PRESETS as $key => $label)
            <a class="chip {{ $range->preset === $key ? 'is-active' : '' }}"
               href="{{ route($routeName, array_merge(request()->except(['range', 'from', 'to', 'page']), ['range' => $key])) }}">
                @if ($range->preset === $key) {!! icon('check') !!} @endif
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form class="range-custom" action="{{ route($routeName) }}" method="get">
        @foreach (request()->except(['range', 'from', 'to', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        <label for="range-from">From</label>
        <input class="control" id="range-from" type="date" name="from"
               value="{{ $range->preset === 'custom' ? $range->from->toDateString() : '' }}" max="{{ now()->toDateString() }}">

        <label for="range-to">To</label>
        <input class="control" id="range-to" type="date" name="to"
               value="{{ $range->preset === 'custom' ? $range->to->toDateString() : '' }}" max="{{ now()->toDateString() }}">

        <button class="btn btn-primary btn-sm" type="submit">{!! icon('calendar') !!} Apply</button>
    </form>

    <div class="range-actions">
        <a class="chip {{ $range->includeBots ? 'is-active' : '' }}"
           href="{{ route($routeName, array_merge(request()->except(['bots', 'page']), $range->includeBots ? [] : ['bots' => 1])) }}"
           title="Crawlers and monitoring tools are excluded from reports by default">
            @if ($range->includeBots) {!! icon('check') !!} @endif Include bots
        </a>
    </div>
</div>

<p class="range-summary">
    {!! icon('calendar') !!}
    <span><strong>{{ $range->label() }}</strong> · {{ $range->subtitle() }}
        @unless ($range->includeBots) · people only @endunless
    </span>
</p>

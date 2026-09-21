{{-- Magnitude comparison: one hue, more-is-longer, value at the tip. --}}
@php
    $rows = collect($rows ?? []);
    $max = max(1, (int) $rows->max($valueKey ?? 'views'));
    $valueKey = $valueKey ?? 'views';
@endphp

<div class="card">
    <div class="card-head">
        <h3>{{ $title }}</h3>
        @if (! empty($action))
            <a class="btn btn-outline btn-sm" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
        @endif
    </div>

    <div class="card-body">
        @if ($rows->isEmpty())
            <div class="empty" style="padding:26px 10px">{!! icon($icon ?? 'chart') !!}<p>{{ $emptyText ?? 'Nothing recorded yet.' }}</p></div>
        @else
            <ul class="bar-list">
                @foreach ($rows as $row)
                    @php($value = (int) data_get($row, $valueKey))
                    <li>
                        <span class="bar-label" title="{{ data_get($row, $labelKey) }}">
                            {{ data_get($row, $labelKey) ?: 'Unknown' }}
                            @if (! empty($subKey) && data_get($row, $subKey))
                                <small>{{ data_get($row, $subKey) }}</small>
                            @endif
                        </span>
                        <span class="bar-track">
                            <span class="bar-fill" style="width:{{ max(2, round($value / $max * 100)) }}%"></span>
                        </span>
                        <span class="bar-value">{{ number_format($value) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

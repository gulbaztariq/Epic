@extends('layouts.admin')

@section('title', $title)
@section('heading', $title)
@section('crumb', $description ?: 'Manage '.Str::lower($title))

@section('content')
    <div class="page-head">
        <div>
            <h2 style="margin:0">{{ $records->total() }} {{ Str::lower(Str::plural($singular, $records->total())) }}</h2>
            @if ($description)<p>{{ $description }}</p>@endif
        </div>
        <a class="btn btn-primary" href="{{ route('admin.'.$uri.'.create') }}">{!! icon('plus') !!} Add {{ Str::lower($singular) }}</a>
    </div>

    @if ($note)
        <div class="help-note">{!! $note !!}</div>
    @endif

    <div class="toolbar">
        <form action="{{ route('admin.'.$uri.'.index') }}" method="get">
            <input class="control" type="search" name="q" value="{{ $search }}" placeholder="Search {{ Str::lower($title) }}…">
            @foreach ($filters as $field => $filter)
                <select class="control" name="{{ $field }}" onchange="this.form.submit()">
                    <option value="">All {{ Str::lower($filter['label']) }}</option>
                    @foreach ($filter['options'] as $value => $label)
                        <option value="{{ $value }}" @selected(request($field) === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @endforeach
            <button class="btn btn-outline" type="submit">{!! icon('search') !!} Filter</button>
            @if ($search || collect($filters)->keys()->contains(fn ($f) => request()->filled($f)))
                <a class="btn btn-outline" href="{{ route('admin.'.$uri.'.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th>{{ $column['label'] }}</th>
                        @endforeach
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            @foreach ($columns as $column)
                                @php($value = data_get($record, $column['key']))
                                <td>
                                    @switch($column['type'] ?? 'text')
                                        @case('title')
                                            <span class="row-title">{{ $value ?: '—' }}</span>
                                            @if (! empty($column['sub']) && data_get($record, $column['sub']))
                                                <span class="row-sub">{{ Str::limit(strip_tags((string) data_get($record, $column['sub'])), 70) }}</span>
                                            @endif
                                            @break

                                        @case('image')
                                            @if ($value)
                                                <img class="thumb {{ ! empty($column['round']) ? 'is-square' : '' }}" src="{{ uploaded_url($value) }}" alt="">
                                            @else
                                                <span class="thumb {{ ! empty($column['round']) ? 'is-square' : '' }}" style="display:inline-block"></span>
                                            @endif
                                            @break

                                        @case('badge')
                                            @php($label = $column['map'][$value] ?? $value)
                                            @if (filled($label))
                                                <span class="badge {{ $column['tone'] ?? 'badge-blue' }}">{{ $label }}</span>
                                            @else — @endif
                                            @break

                                        @case('boolean')
                                            <span class="badge {{ $value ? 'badge-green' : 'badge-grey' }}">
                                                {{ $value ? ($column['on'] ?? 'Published') : ($column['off'] ?? 'Draft') }}
                                            </span>
                                            @break

                                        @case('date')
                                            {{ $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '—' }}
                                            @break

                                        @case('datetime')
                                            {{ $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i') : '—' }}
                                            @break

                                        @case('icon')
                                            <span style="display:inline-flex;align-items:center;gap:8px">
                                                <span style="width:26px;height:26px;color:var(--blue)">{!! icon($value) !!}</span>
                                                <span class="row-sub" style="margin:0">{{ $value }}</span>
                                            </span>
                                            @break

                                        @default
                                            {{ filled($value) ? Str::limit(strip_tags((string) $value), $column['limit'] ?? 60) : '—' }}
                                    @endswitch
                                </td>
                            @endforeach

                            <td class="actions">
                                <a class="btn btn-outline btn-sm" href="{{ route('admin.'.$uri.'.edit', $record) }}">{!! icon('edit') !!} Edit</a>
                                <form action="{{ route('admin.'.$uri.'.destroy', $record) }}" method="post"
                                      data-confirm="Delete this {{ Str::lower($singular) }}? This cannot be undone.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">{!! icon('trash') !!}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}">
                                <div class="empty">
                                    {!! icon($resourceIcon) !!}
                                    <p>No {{ Str::lower($title) }} yet.</p>
                                    <a class="btn btn-primary btn-sm" href="{{ route('admin.'.$uri.'.create') }}">{!! icon('plus') !!} Add the first one</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $records->links('vendor.pagination.epic') }}
    </div>
@endsection

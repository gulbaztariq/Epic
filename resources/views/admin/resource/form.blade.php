@extends('layouts.admin')

@section('title', ($isNew ? 'Add ' : 'Edit ').Str::lower($singular))
@section('heading', ($isNew ? 'Add ' : 'Edit ').Str::lower($singular))
@section('crumb', $title)

@section('content')
    <div class="page-head">
        <div>
            <h2 style="margin:0">{{ $isNew ? 'New '.Str::lower($singular) : ($record->title ?? $record->name ?? $record->label ?? 'Edit record') }}</h2>
            @if ($description)<p>{{ $description }}</p>@endif
        </div>
        <a class="btn btn-outline" href="{{ route('admin.'.$uri.'.index') }}">{!! icon('arrow-left') !!} Back to {{ Str::lower($title) }}</a>
    </div>

    <form action="{{ $action }}" method="post" enctype="multipart/form-data">
        @csrf
        @if ($method !== 'POST') @method($method) @endif

        <div class="card">
            <div class="card-body">
                <div class="form-grid">
                    @foreach ($fields as $field)
                        @include('admin.resource.field', ['field' => $field, 'record' => $record])
                    @endforeach
                </div>
            </div>

            <div class="card-foot">
                <button class="btn btn-primary" type="submit">{!! icon('check') !!} {{ $isNew ? 'Create' : 'Save changes' }}</button>
                <a class="btn btn-outline" href="{{ route('admin.'.$uri.'.index') }}">Cancel</a>
            </div>
        </div>
    </form>

    @if (! empty($partial))
        @include($partial, ['album' => $record])
    @endif

    @unless ($isNew)
        <form class="mt-3" action="{{ route('admin.'.$uri.'.destroy', $record) }}" method="post" style="margin-top:18px"
              data-confirm="Delete this {{ Str::lower($singular) }}? This cannot be undone.">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger" type="submit">{!! icon('trash') !!} Delete {{ Str::lower($singular) }}</button>
        </form>
    @endunless

    {{-- Icon previews used by the icon picker --}}
    @foreach (\App\Support\Icons::names() as $iconName)
        <template data-icon-svg="{{ $iconName }}">{!! icon($iconName) !!}</template>
    @endforeach
@endsection

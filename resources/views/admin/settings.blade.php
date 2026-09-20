@extends('layouts.admin')

@section('title', 'Site settings')
@section('heading', 'Site settings')
@section('crumb', 'Branding, contact details, social media and integrations')

@section('content')
    <div class="tabs">
        @foreach ($schema as $key => $tab)
            <a class="{{ $group === $key ? 'is-active' : '' }}" href="{{ route('admin.settings', $key) }}">{{ $tab['label'] }}</a>
        @endforeach
    </div>

    <form action="{{ route('admin.settings.update', $group) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-head">
                <h3>{{ $schema[$group]['label'] }}</h3>
            </div>

            <div class="card-body">
                <div class="form-grid">
                    @foreach ($fields as $key => $meta)
                        @php($value = old($key, setting($key)))
                        <div class="field col-{{ $meta['col'] ?? 12 }}">
                            <label for="s-{{ $key }}">{!! $meta['label'] !!}</label>

                            @if ($meta['type'] === 'textarea')
                                <textarea class="control" id="s-{{ $key }}" name="{{ $key }}" rows="{{ $meta['rows'] ?? 4 }}"
                                          placeholder="{{ $meta['placeholder'] ?? '' }}">{{ $value }}</textarea>
                            @elseif ($meta['type'] === 'image')
                                <div class="media-field">
                                    @if (filled(setting($key)))
                                        <div class="media-preview">
                                            <img src="{{ uploaded_url(setting($key)) }}" alt="" data-preview-for="{{ $key }}">
                                            <span class="file-chip">{{ basename(setting($key)) }}</span>
                                        </div>
                                    @else
                                        <div class="media-preview"><img src="" alt="" data-preview-for="{{ $key }}" style="display:none"></div>
                                    @endif
                                    <input type="file" id="s-{{ $key }}" name="{{ $key }}" accept="image/*" data-preview="{{ $key }}">
                                    @if (filled(setting($key)))
                                        <label class="media-remove"><input type="checkbox" name="remove_{{ $key }}" value="1"> Remove current image</label>
                                    @endif
                                </div>
                            @else
                                <input class="control" id="s-{{ $key }}" type="text" name="{{ $key }}" value="{{ $value }}"
                                       placeholder="{{ $meta['placeholder'] ?? '' }}">
                            @endif

                            @if (! empty($meta['hint']))<span class="hint">{!! $meta['hint'] !!}</span>@endif
                            @error($key)<span class="error">{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card-foot">
                <button class="btn btn-primary" type="submit">{!! icon('check') !!} Save settings</button>
            </div>
        </div>
    </form>
@endsection

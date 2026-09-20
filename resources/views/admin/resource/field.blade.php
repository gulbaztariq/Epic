@php
    $name = $field['name'];
    $type = $field['type'];
    $label = $field['label'] ?? Str::headline($name);
    $col = $field['col'] ?? 12;
    $value = old($name, $record->{$name} ?? ($field['default'] ?? null));
    if ($value instanceof \DateTimeInterface) {
        $value = $type === 'datetime' ? $value->format('Y-m-d\TH:i') : $value->format('Y-m-d');
    }
    $required = isset($field['rules']) && is_string($field['rules']) && str_contains($field['rules'], 'required');
    $invalid = $errors->has($name) ? 'is-invalid' : '';
@endphp

@if ($type === 'section')
    <div class="field-section"><h3>{{ $label }}</h3></div>
@else
    <div class="field col-{{ $col }}">
        @unless ($type === 'checkbox')
            <label for="f-{{ $name }}">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
        @endunless

        @switch($type)
            @case('textarea')
                <textarea class="control {{ $invalid }}" id="f-{{ $name }}" name="{{ $name }}"
                          rows="{{ $field['rows'] ?? 4 }}" placeholder="{{ $field['placeholder'] ?? '' }}">{{ $value }}</textarea>
                @break

            @case('richtext')
                <textarea class="control {{ $invalid }} is-tall" id="f-{{ $name }}" name="{{ $name }}" data-richtext
                          placeholder="{{ $field['placeholder'] ?? '' }}">{{ $value }}</textarea>
                @break

            @case('select')
                <select class="control {{ $invalid }}" id="f-{{ $name }}" name="{{ $name }}">
                    @if (! empty($field['placeholder']))
                        <option value="">{{ $field['placeholder'] }}</option>
                    @endif
                    @foreach ($field['options'] ?? [] as $optValue => $optLabel)
                        <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                    @endforeach
                </select>
                @break

            @case('icon')
                <div class="icon-pick">
                    <span class="icon-preview" data-icon-preview="{{ $name }}">{!! icon($value ?: 'chart') !!}</span>
                    <select class="control {{ $invalid }}" id="f-{{ $name }}" name="{{ $name }}" data-icon-select="{{ $name }}">
                        @if (! ($field['required'] ?? false))
                            <option value="">No icon</option>
                        @endif
                        @foreach (\App\Support\Icons::names() as $iconName)
                            <option value="{{ $iconName }}" @selected($value === $iconName)>{{ $iconName }}</option>
                        @endforeach
                    </select>
                </div>
                @break

            @case('checkbox')
                <div class="switch">
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input type="checkbox" id="f-{{ $name }}" name="{{ $name }}" value="1" @checked((bool) $value)>
                    <label for="f-{{ $name }}">{{ $label }}</label>
                </div>
                @break

            @case('image')
            @case('file')
                <div class="media-field">
                    @if (filled($record->{$name} ?? null))
                        <div class="media-preview">
                            @if ($type === 'image')
                                <img src="{{ uploaded_url($record->{$name}) }}" alt="" data-preview-for="{{ $name }}">
                            @else
                                {!! icon('document') !!}
                            @endif
                            <a class="file-chip" href="{{ uploaded_url($record->{$name}) }}" target="_blank" rel="noopener">{{ basename($record->{$name}) }}</a>
                        </div>
                    @elseif ($type === 'image')
                        <div class="media-preview">
                            <img src="" alt="" data-preview-for="{{ $name }}" style="display:none">
                        </div>
                    @endif

                    <input class="{{ $invalid }}" type="file" id="f-{{ $name }}" name="{{ $name }}"
                           data-preview="{{ $name }}"
                           accept="{{ $field['accept'] ?? ($type === 'image' ? 'image/*' : '') }}">

                    @if (filled($record->{$name} ?? null))
                        <label class="media-remove">
                            <input type="checkbox" name="remove_{{ $name }}" value="1">
                            Remove current {{ $type }}
                        </label>
                    @endif
                </div>
                @break

            @case('date')
                <input class="control {{ $invalid }}" id="f-{{ $name }}" type="date" name="{{ $name }}" value="{{ $value }}">
                @break

            @case('datetime')
                <input class="control {{ $invalid }}" id="f-{{ $name }}" type="datetime-local" name="{{ $name }}" value="{{ $value }}">
                @break

            @case('number')
                <input class="control {{ $invalid }}" id="f-{{ $name }}" type="number" name="{{ $name }}" value="{{ $value }}"
                       step="{{ $field['step'] ?? 1 }}" min="{{ $field['min'] ?? 0 }}">
                @break

            @case('password')
                <input class="control {{ $invalid }}" id="f-{{ $name }}" type="password" name="{{ $name }}"
                       autocomplete="new-password" placeholder="{{ $field['placeholder'] ?? '' }}">
                @break

            @case('email')
                <input class="control {{ $invalid }}" id="f-{{ $name }}" type="email" name="{{ $name }}" value="{{ $value }}"
                       placeholder="{{ $field['placeholder'] ?? '' }}">
                @break

            @case('url')
                <input class="control {{ $invalid }}" id="f-{{ $name }}" type="url" name="{{ $name }}" value="{{ $value }}"
                       placeholder="{{ $field['placeholder'] ?? 'https://' }}">
                @break

            @default
                <input class="control {{ $invalid }}" id="f-{{ $name }}" type="text" name="{{ $name }}" value="{{ $value }}"
                       placeholder="{{ $field['placeholder'] ?? '' }}"
                       @if ($name === 'title' || $name === 'name') data-slug-source @endif
                       @if ($name === 'slug') data-slug-target @endif>
        @endswitch

        @if (! empty($field['hint']))
            <span class="hint">{!! $field['hint'] !!}</span>
        @endif

        @error($name)
            <span class="error">{{ $message }}</span>
        @enderror
    </div>
@endif

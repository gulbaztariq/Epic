{{--
    Fit & crop controls for one image field: how the picture sits in its frame on the
    website. Posts pic[<field>][fit|x|y|zoom]; the form engine saves them against the
    picture's path. Included by field.blade.php for every image field unless the field
    sets 'adjust' => false (logos and flags have no frame to fit).

    Expects: $name, $path, $aspect (preview frame shape), $auto (what "Automatic" means here).
    Optional: $domId, to keep element ids unique when several pictures share a page.
--}}
@php
    $domId = $domId ?? $name;
    $current = \App\Support\Pictures::settings($path);
    $fitValue = old('pic.'.$name.'.fit', $current['fit'] ?? 'auto');
    $xValue = old('pic.'.$name.'.x', $current['x']);
    $yValue = old('pic.'.$name.'.y', $current['y']);
    $zoomValue = old('pic.'.$name.'.zoom', $current['zoom']);
@endphp

<div class="pic-adjust" data-pic-adjust="{{ $name }}" data-auto-fit="{{ $auto }}" @if (blank($path)) hidden @endif>
    <div class="pic-adjust-head">
        <strong>Fit &amp; crop</strong>
        <button class="btn btn-sm btn-outline" type="button" data-pic-reset>Reset</button>
    </div>

    <div class="pic-adjust-body">
        <div class="pic-frame" style="aspect-ratio: {{ $aspect }}" data-pic-frame title="Click to choose the focal point">
            <img src="{{ filled($path) ? uploaded_url($path) : '' }}" alt="" data-pic-preview>
            <span class="pic-dot" data-pic-dot></span>
        </div>

        <div class="pic-controls">
            <div class="field">
                <label for="pic-{{ $domId }}-fit">How the picture fits</label>
                <select class="control" id="pic-{{ $domId }}-fit" name="pic[{{ $name }}][fit]" data-pic-fit>
                    @foreach (\App\Support\Pictures::FITS as $value => $label)
                        <option value="{{ $value }}" @selected((string) $fitValue === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="pic-{{ $domId }}-x">Focal point, left to right <output data-pic-out="x">{{ $xValue }}%</output></label>
                <input type="range" id="pic-{{ $domId }}-x" name="pic[{{ $name }}][x]" min="0" max="100" step="1" value="{{ $xValue }}" data-pic-x>
            </div>

            <div class="field">
                <label for="pic-{{ $domId }}-y">Focal point, top to bottom <output data-pic-out="y">{{ $yValue }}%</output></label>
                <input type="range" id="pic-{{ $domId }}-y" name="pic[{{ $name }}][y]" min="0" max="100" step="1" value="{{ $yValue }}" data-pic-y>
            </div>

            <div class="field">
                <label for="pic-{{ $domId }}-zoom">Zoom in <output data-pic-out="zoom">{{ $zoomValue }}%</output></label>
                <input type="range" id="pic-{{ $domId }}-zoom" name="pic[{{ $name }}][zoom]"
                       min="{{ \App\Support\Pictures::MIN_ZOOM }}" max="{{ \App\Support\Pictures::MAX_ZOOM }}" step="5" value="{{ $zoomValue }}" data-pic-zoom>
            </div>

            <span class="hint"><strong>Automatic</strong> shows the whole picture (page headers fill their width). <strong>Fill</strong> crops the edges to cover the frame; <strong>Stretch</strong> can distort the picture. Click the preview to move the focal point — the part of the picture that stays in view when it is cropped or zoomed. The preview shows the effect as you change it.</span>
        </div>
    </div>
</div>

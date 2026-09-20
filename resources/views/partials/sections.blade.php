{{-- Renders the flexible content blocks an admin attaches to a page. --}}
@php($blocks = $page->activeSections ?? collect())

@foreach ($blocks as $block)
    @php($items = $block->list_group ? \App\Models\ListItem::inGroup($block->list_group) : collect())

    @switch($block->type)
        @case('text')
            <section class="section {{ $loop->even ? 'section-soft' : '' }}">
                <div class="container container-narrow">
                    @if ($block->heading)<h2 class="section-title">{{ $block->heading }}</h2>@endif
                    @if ($block->subheading)<p class="lead">{{ $block->subheading }}</p>@endif
                    <div class="prose">{!! rich($block->body) !!}</div>
                    @if ($block->link_text)
                        <a class="btn btn-primary mt-3" href="{{ $block->link_url ?: '#' }}">{{ $block->link_text }} {!! icon('arrow-right') !!}</a>
                    @endif
                </div>
            </section>
            @break

        @case('list')
            <section class="section {{ $loop->even ? 'section-soft' : '' }}">
                <div class="container">
                    @if ($block->heading)<h2 class="section-title">{{ $block->heading }}</h2>@endif
                    @if ($block->subheading)<p class="lead" style="max-width:70ch">{{ $block->subheading }}</p>@endif
                    <div class="mt-4"><x-check-list :items="$items" :columns="2" /></div>
                </div>
            </section>
            @break

        @case('cards')
            <section class="section {{ $loop->even ? 'section-soft' : '' }}">
                <div class="container">
                    @if ($block->heading)<h2 class="section-title">{{ $block->heading }}</h2>@endif
                    @if ($block->subheading)<p class="lead" style="max-width:70ch">{{ $block->subheading }}</p>@endif
                    <div class="grid grid-3 mt-4">
                        @foreach ($items as $item)
                            <div class="card" style="padding:24px">
                                <div class="principle-icon" style="margin-bottom:14px">{!! icon($item->icon ?: 'sparkle') !!}</div>
                                <h3 style="font-size:1.12rem">{{ $item->title }}</h3>
                                <p style="font-size:.92rem;margin:0">{{ $item->description }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @break

        @case('image_text')
            <section class="section {{ $loop->even ? 'section-soft' : '' }}">
                <div class="container">
                    <div class="split {{ $loop->odd ? 'is-reverse' : '' }}">
                        <div class="split-media">
                            <img src="{{ epic_image($block->image, 'card') }}" alt="{{ $block->heading }}" loading="lazy">
                        </div>
                        <div>
                            @if ($block->heading)<h2 class="section-title">{{ $block->heading }}</h2>@endif
                            @if ($block->subheading)<p class="lead">{{ $block->subheading }}</p>@endif
                            <div class="prose">{!! rich($block->body) !!}</div>
                            @if ($block->link_text)
                                <a class="btn btn-primary mt-3" href="{{ $block->link_url ?: '#' }}">{{ $block->link_text }} {!! icon('arrow-right') !!}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
            @break

        @case('quote')
            <section class="section section-sm">
                <div class="container container-narrow text-center">
                    {!! icon('quote', 'icon', null) !!}
                    <blockquote style="border:0;font-size:1.5rem;text-align:center;padding:0;margin:14px 0 0">{{ $block->body ?: $block->heading }}</blockquote>
                    @if ($block->subheading)<p class="text-muted mt-3">{{ $block->subheading }}</p>@endif
                </div>
            </section>
            @break

        @case('accordion')
            <section class="section {{ $loop->even ? 'section-soft' : '' }}">
                <div class="container container-narrow">
                    @if ($block->heading)<h2 class="section-title">{{ $block->heading }}</h2>@endif
                    <div class="accordion mt-4" data-single>
                        @foreach ($items as $item)
                            <div class="accordion-item {{ $loop->first ? 'is-open' : '' }}">
                                <button class="accordion-trigger" type="button" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $item->title }} {!! icon('chevron-down') !!}
                                </button>
                                <div class="accordion-panel">{!! rich($item->description) !!}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            @break

        {{-- 'cta' blocks are rendered by the page's call-to-action band. --}}
    @endswitch
@endforeach

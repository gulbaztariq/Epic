@extends('layouts.site')

@section('title', $post->title.' | '.setting('site_name', 'EPIC'))
@section('meta_description', summarise($post->summary, 155))
@section('og_image', epic_image($post->image, 'wide'))

@section('content')
    @php($isPress = $post->category === 'press_release')

    <x-page-hero
        :title="$post->title"
        :eyebrow="$post->category_label"
        :breadcrumbs="$isPress
            ? ['Media' => null, 'Press Releases' => route('media.press'), $post->title => null]
            : ['Blogs & Articles' => route('blogs.index'), $post->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div>
                    <img src="{{ epic_image($post->image, 'wide') }}" alt="{{ $post->title }}"
                         style="border-radius:var(--radius-lg);width:100%;margin-bottom:26px">

                    <div class="meta-row">
                        @if ($post->published_at)<span>{!! icon('calendar') !!}{{ $post->published_at->format('d F Y') }}</span>@endif
                        @if ($post->author)<span>{!! icon('people') !!}{{ $post->author }}</span>@endif
                        @if ($post->tags)<span>{!! icon('layers') !!}{{ $post->tags }}</span>@endif
                    </div>

                    @if ($post->excerpt)
                        <p class="lead" style="color:var(--navy)">{{ $post->excerpt }}</p>
                    @endif

                    <div class="prose">{!! rich($post->body) !!}</div>

                    <div class="share-row">
                        <span>Share</span>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="Share on LinkedIn">{!! icon('linkedin') !!}</a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" aria-label="Share on X">{!! icon('x-social') !!}</a>
                        <a href="mailto:?subject={{ rawurlencode($post->title) }}&body={{ rawurlencode(url()->current()) }}" aria-label="Share by email">{!! icon('mail') !!}</a>
                    </div>
                </div>

                <aside class="sidebar">
                    @if ($related->isNotEmpty())
                        <div class="sidebar-box">
                            <h4>{{ $isPress ? 'More press releases' : 'Related reading' }}</h4>
                            <ul>
                                @foreach ($related as $item)
                                    <li><a href="{{ $item->publicUrl() }}">{{ $item->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="sidebar-box">
                        <h4>Stay informed</h4>
                        <p style="font-size:.9rem">Get EPIC research, events and insights in your inbox.</p>
                        <form action="{{ route('subscribe.store') }}" method="post">
                            @csrf
                            <input type="hidden" name="source" value="article-sidebar">
                            <input class="form-control" type="email" name="email" placeholder="Your email" required>
                            <button class="btn btn-primary btn-block mt-3" type="submit">Subscribe</button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection

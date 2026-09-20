@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Get Involved' => null, $page->title => null]" />

    <section class="section">
        <div class="container container-narrow">
            @if ($page->intro)<p class="lead">{{ $page->intro }}</p>@endif

            <div class="form-card mt-4">
                @include('partials.flash')

                <form action="{{ route('subscribe.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="source" value="subscribe-page">
                    <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">

                    <div class="form-grid">
                        <div class="form-field">
                            <label for="s-name">Name</label>
                            <input class="form-control" id="s-name" type="text" name="name" value="{{ old('name') }}">
                        </div>
                        <div class="form-field">
                            <label for="s-email">Email <span class="req">*</span></label>
                            <input class="form-control" id="s-email" type="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="form-field is-full">
                            <label for="s-org">Organisation</label>
                            <input class="form-control" id="s-org" type="text" name="organisation" value="{{ old('organisation') }}">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary btn-lg" type="submit">Subscribe {!! icon('arrow-right') !!}</button>
                    </div>
                    <p class="form-hint mt-3">{{ setting('subscribe_privacy_note', 'We use your details only to send EPIC research, events and updates. You can unsubscribe at any time.') }}</p>
                </form>
            </div>

            <div class="prose mt-4">{!! rich($page->body) !!}</div>
        </div>
    </section>

    @include('partials.sections')
@endsection

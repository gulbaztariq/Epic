@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="[$page->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div class="form-card">
                    <h2 class="section-title" style="font-size:1.5rem">Send us a message</h2>
                    @include('partials.flash')

                    <form action="{{ route('contact.store') }}" method="post">
                        @csrf
                        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="form-grid">
                            <div class="form-field">
                                <label for="c-name">Full name <span class="req">*</span></label>
                                <input class="form-control" id="c-name" type="text" name="name" value="{{ old('name') }}" required>
                            </div>
                            <div class="form-field">
                                <label for="c-email">Email <span class="req">*</span></label>
                                <input class="form-control" id="c-email" type="email" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="form-field">
                                <label for="c-phone">Phone</label>
                                <input class="form-control" id="c-phone" type="text" name="phone" value="{{ old('phone') }}">
                            </div>
                            <div class="form-field">
                                <label for="c-org">Organisation</label>
                                <input class="form-control" id="c-org" type="text" name="organisation" value="{{ old('organisation') }}">
                            </div>
                            <div class="form-field is-full">
                                <label for="c-subject">Subject</label>
                                <input class="form-control" id="c-subject" type="text" name="subject" value="{{ old('subject') }}">
                            </div>
                            <div class="form-field is-full">
                                <label for="c-message">Message <span class="req">*</span></label>
                                <textarea class="form-control" id="c-message" name="message" rows="6" required>{{ old('message') }}</textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="btn btn-primary btn-lg" type="submit">Send message {!! icon('arrow-right') !!}</button>
                        </div>
                    </form>
                </div>

                <aside class="sidebar">
                    <div class="sidebar-box">
                        <h4>Contact details</h4>
                        <ul class="contact-lines">
                            @if (setting('contact_address'))
                                <li>{!! icon('location') !!}<div><strong>Address</strong><span>{{ setting('contact_address') }}</span></div></li>
                            @endif
                            @if (setting('contact_email'))
                                <li>{!! icon('mail') !!}<div><strong>Email</strong><a href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></div></li>
                            @endif
                            @if (setting('contact_phone'))
                                <li>{!! icon('phone') !!}<div><strong>Phone</strong><a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('contact_phone')) }}">{{ setting('contact_phone') }}</a></div></li>
                            @endif
                            @if (setting('office_hours'))
                                <li>{!! icon('clock') !!}<div><strong>Office hours</strong><span>{{ setting('office_hours') }}</span></div></li>
                            @endif
                        </ul>

                        @if (count(social_links()))
                            <div class="social-row mt-3" style="margin-top:18px">
                                @foreach (social_links() as $link)
                                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener" aria-label="{{ $link['label'] }}"
                                       style="background:var(--bg-soft);color:var(--navy)">{!! icon($link['icon']) !!}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="sidebar-box">
                        <h4>Other enquiries</h4>
                        <ul>
                            <li><a href="{{ route('involved.careers') }}">Careers at EPIC</a></li>
                            <li><a href="{{ route('involved.volunteer') }}">Volunteer with us</a></li>
                            <li><a href="{{ route('partnerships.index') }}">Partnerships &amp; MoUs</a></li>
                            <li><a href="{{ route('media.press') }}">Media &amp; press</a></li>
                        </ul>
                    </div>
                </aside>
            </div>

            @if (setting('map_embed'))
                <div class="map-frame mt-4" style="margin-top:44px">{!! setting('map_embed') !!}</div>
            @endif
        </div>
    </section>

    @include('partials.sections')
@endsection

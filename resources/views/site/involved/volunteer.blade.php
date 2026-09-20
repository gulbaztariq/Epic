@extends('layouts.site')

@section('title', ($page->meta_title ?: $page->title).' | '.setting('site_name', 'EPIC'))
@section('meta_description', $page->meta_description ?: summarise($page->intro, 155))

@section('content')
    <x-page-hero :page="$page" :breadcrumbs="['Get Involved' => null, $page->title => null]" />

    <section class="section">
        <div class="container">
            <div class="content-layout">
                <div>
                    @if ($page->intro)<p class="lead">{{ $page->intro }}</p>@endif
                    <div class="prose">{!! rich($page->body) !!}</div>

                    <div class="form-card mt-4">
                        <h2 class="section-title" style="font-size:1.4rem">Volunteer application</h2>
                        @include('partials.flash')

                        <form action="{{ route('involved.volunteer.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">

                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="v-name">Full name <span class="req">*</span></label>
                                    <input class="form-control" id="v-name" type="text" name="name" value="{{ old('name') }}" required>
                                </div>
                                <div class="form-field">
                                    <label for="v-email">Email <span class="req">*</span></label>
                                    <input class="form-control" id="v-email" type="email" name="email" value="{{ old('email') }}" required>
                                </div>
                                <div class="form-field">
                                    <label for="v-phone">Phone</label>
                                    <input class="form-control" id="v-phone" type="text" name="phone" value="{{ old('phone') }}">
                                </div>
                                <div class="form-field">
                                    <label for="v-city">City</label>
                                    <input class="form-control" id="v-city" type="text" name="city" value="{{ old('city') }}">
                                </div>
                                <div class="form-field">
                                    <label for="v-country">Country</label>
                                    <input class="form-control" id="v-country" type="text" name="country" value="{{ old('country') }}">
                                </div>
                                <div class="form-field">
                                    <label for="v-interest">Area of interest</label>
                                    <select class="form-control" id="v-interest" name="interest">
                                        <option value="">Please select…</option>
                                        @foreach ($ways as $way)
                                            <option value="{{ $way->title }}" @selected(old('interest') === $way->title)>{{ $way->title }}</option>
                                        @endforeach
                                        <option value="Research support" @selected(old('interest') === 'Research support')>Research support</option>
                                        <option value="Events and outreach" @selected(old('interest') === 'Events and outreach')>Events and outreach</option>
                                        <option value="Communications" @selected(old('interest') === 'Communications')>Communications</option>
                                        <option value="Other" @selected(old('interest') === 'Other')>Other</option>
                                    </select>
                                </div>
                                <div class="form-field">
                                    <label for="v-availability">Availability</label>
                                    <input class="form-control" id="v-availability" type="text" name="availability" value="{{ old('availability') }}" placeholder="e.g. 10 hours per week">
                                </div>
                                <div class="form-field">
                                    <label for="v-cv">CV (PDF or Word, max 5 MB)</label>
                                    <input class="form-control" id="v-cv" type="file" name="cv" accept=".pdf,.doc,.docx">
                                </div>
                                <div class="form-field is-full">
                                    <label for="v-message">Tell us how you would like to contribute</label>
                                    <textarea class="form-control" id="v-message" name="message" rows="5">{{ old('message') }}</textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button class="btn btn-primary btn-lg" type="submit">Submit application {!! icon('arrow-right') !!}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <aside class="sidebar">
                    @if ($ways->isNotEmpty())
                        <div class="sidebar-box">
                            <h4>Ways to engage</h4>
                            <ul class="contact-lines" style="gap:14px">
                                @foreach ($ways as $way)
                                    <li>{!! icon($way->icon ?: 'check') !!}<div><strong>{{ $way->title }}</strong><span>{{ $way->description }}</span></div></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="sidebar-box">
                        <h4>Prefer to talk first?</h4>
                        <p style="font-size:.9rem">Write to us and a member of the EPIC team will get back to you.</p>
                        <a class="btn btn-outline btn-block" href="{{ route('contact') }}">Contact EPIC</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @include('partials.sections')
@endsection

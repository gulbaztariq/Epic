@extends('layouts.admin')

@section('title', 'Volunteer application')
@section('heading', 'Volunteer application')
@section('crumb', $application->name)

@section('content')
    <div class="page-head">
        <div><h2 style="margin:0">{{ $application->name }}</h2></div>
        <a class="btn btn-outline" href="{{ route('admin.volunteers') }}">{!! icon('arrow-left') !!} Back to applications</a>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head"><h3>Details</h3></div>
            <div class="card-body">
                <table class="data">
                    <tbody>
                        <tr><th style="width:150px">Email</th><td><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></td></tr>
                        <tr><th>Phone</th><td>{{ $application->phone ?: '—' }}</td></tr>
                        <tr><th>Location</th><td>{{ collect([$application->city, $application->country])->filter()->implode(', ') ?: '—' }}</td></tr>
                        <tr><th>Interest</th><td>{{ $application->interest ?: '—' }}</td></tr>
                        <tr><th>Availability</th><td>{{ $application->availability ?: '—' }}</td></tr>
                        <tr><th>CV</th><td>
                            @if ($application->cv_path)
                                <a href="{{ uploaded_url($application->cv_path) }}" target="_blank" rel="noopener">{!! icon('download') !!} Download CV</a>
                            @else — @endif
                        </td></tr>
                        <tr><th>Received</th><td>{{ $application->created_at->format('d M Y, H:i') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-foot">
                <a class="btn btn-primary" href="mailto:{{ $application->email }}?subject={{ rawurlencode('Volunteering with EPIC') }}">{!! icon('mail') !!} Reply by email</a>
                <form action="{{ route('admin.volunteers.destroy', $application) }}" method="post" data-confirm="Delete this application?">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" type="submit">{!! icon('trash') !!} Delete</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Message</h3></div>
            <div class="card-body" style="white-space:pre-line">{{ $application->message ?: 'No message provided.' }}</div>
        </div>
    </div>
@endsection

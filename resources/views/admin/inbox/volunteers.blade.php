@extends('layouts.admin')

@section('title', 'Volunteers')
@section('heading', 'Volunteer applications')
@section('crumb', 'People who applied to volunteer with EPIC')

@section('content')
    <div class="toolbar">
        <form action="{{ route('admin.volunteers') }}" method="get">
            <input class="control" type="search" name="q" value="{{ request('q') }}" placeholder="Search by name or email…">
            <button class="btn btn-outline" type="submit">{!! icon('search') !!} Search</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>Applicant</th><th>Interest</th><th>Location</th><th>Received</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>
                                <span class="row-title">{{ $application->name }}</span>
                                <span class="row-sub">{{ $application->email }}</span>
                            </td>
                            <td>
                                {{ $application->interest ?: '—' }}
                                @unless ($application->is_read)<span class="badge badge-amber">New</span>@endunless
                            </td>
                            <td>{{ collect([$application->city, $application->country])->filter()->implode(', ') ?: '—' }}</td>
                            <td>{{ $application->created_at->format('d M Y') }}</td>
                            <td class="actions">
                                <a class="btn btn-outline btn-sm" href="{{ route('admin.volunteers.show', $application) }}">{!! icon('eye') !!} View</a>
                                <form action="{{ route('admin.volunteers.destroy', $application) }}" method="post" data-confirm="Delete this application?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">{!! icon('trash') !!}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty">{!! icon('heart') !!}<p>No volunteer applications yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $applications->links('vendor.pagination.epic') }}
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Subscribers')
@section('heading', 'Newsletter subscribers')
@section('crumb', $total.' people subscribed to EPIC updates')

@section('content')
    <div class="toolbar">
        <form action="{{ route('admin.subscribers') }}" method="get">
            <input class="control" type="search" name="q" value="{{ request('q') }}" placeholder="Search subscribers…">
            <button class="btn btn-outline" type="submit">{!! icon('search') !!} Search</button>
        </form>
        <div class="spacer"></div>
        <a class="btn btn-green" href="{{ route('admin.subscribers.export') }}">{!! icon('download') !!} Export CSV</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>Email</th><th>Name</th><th>Organisation</th><th>Source</th><th>Subscribed</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($subscribers as $subscriber)
                        <tr>
                            <td><span class="row-title">{{ $subscriber->email }}</span></td>
                            <td>{{ $subscriber->name ?: '—' }}</td>
                            <td>{{ $subscriber->organisation ?: '—' }}</td>
                            <td><span class="badge badge-grey">{{ $subscriber->source ?: 'website' }}</span></td>
                            <td>{{ $subscriber->created_at->format('d M Y') }}</td>
                            <td class="actions">
                                <form action="{{ route('admin.subscribers.destroy', $subscriber) }}" method="post" data-confirm="Remove this subscriber?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">{!! icon('trash') !!}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty">{!! icon('mail') !!}<p>No subscribers yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $subscribers->links('vendor.pagination.epic') }}
    </div>
@endsection

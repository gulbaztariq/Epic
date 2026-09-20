@extends('layouts.admin')

@section('title', 'Messages')
@section('heading', 'Contact messages')
@section('crumb', 'Enquiries submitted through the website contact form')

@section('content')
    <div class="toolbar">
        <form action="{{ route('admin.messages') }}" method="get">
            <input class="control" type="search" name="q" value="{{ request('q') }}" placeholder="Search by name, email or subject…">
            <button class="btn btn-outline" type="submit">{!! icon('search') !!} Search</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>From</th><th>Subject</th><th>Received</th><th style="text-align:right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td>
                                <span class="row-title">{{ $message->name }}</span>
                                <span class="row-sub">{{ $message->email }}</span>
                            </td>
                            <td>
                                {{ Str::limit($message->subject ?: Str::limit($message->message, 50), 60) }}
                                @unless ($message->is_read)<span class="badge badge-amber">New</span>@endunless
                            </td>
                            <td>{{ $message->created_at->format('d M Y, H:i') }}</td>
                            <td class="actions">
                                <a class="btn btn-outline btn-sm" href="{{ route('admin.messages.show', $message) }}">{!! icon('eye') !!} Read</a>
                                <form action="{{ route('admin.messages.destroy', $message) }}" method="post" data-confirm="Delete this message?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">{!! icon('trash') !!}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty">{!! icon('inbox') !!}<p>No messages yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $messages->links('vendor.pagination.epic') }}
    </div>
@endsection

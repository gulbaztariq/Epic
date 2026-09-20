@extends('layouts.admin')

@section('title', 'Message')
@section('heading', 'Message')
@section('crumb', 'From '.$message->name)

@section('content')
    <div class="page-head">
        <div><h2 style="margin:0">{{ $message->subject ?: 'Website enquiry' }}</h2></div>
        <a class="btn btn-outline" href="{{ route('admin.messages') }}">{!! icon('arrow-left') !!} Back to messages</a>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <div class="card-head"><h3>Message</h3></div>
            <div class="card-body" style="white-space:pre-line">{{ $message->message }}</div>
            <div class="card-foot">
                <a class="btn btn-primary" href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Your enquiry to EPIC')) }}">{!! icon('mail') !!} Reply by email</a>
                <form action="{{ route('admin.messages.destroy', $message) }}" method="post" data-confirm="Delete this message?">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger" type="submit">{!! icon('trash') !!} Delete</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>Sender</h3></div>
            <div class="card-body">
                <table class="data">
                    <tbody>
                        <tr><th style="width:150px">Name</th><td>{{ $message->name }}</td></tr>
                        <tr><th>Email</th><td><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></td></tr>
                        <tr><th>Phone</th><td>{{ $message->phone ?: '—' }}</td></tr>
                        <tr><th>Organisation</th><td>{{ $message->organisation ?: '—' }}</td></tr>
                        <tr><th>Received</th><td>{{ $message->created_at->format('d M Y, H:i') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

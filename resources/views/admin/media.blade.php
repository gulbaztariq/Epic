@extends('layouts.admin')

@section('title', 'Media library')
@section('heading', 'Media library')
@section('crumb', 'Every image and document uploaded to the website')

@section('content')
    <div class="card" style="margin-bottom:20px">
        <div class="card-head"><h3>Upload files</h3></div>
        <div class="card-body">
            <form action="{{ route('admin.media.store') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="field col-6">
                        <label for="files">Choose files</label>
                        <input class="control" id="files" type="file" name="files[]" multiple required>
                        <span class="hint">Up to 20 files, max 20 MB each.</span>
                    </div>
                    <div class="field col-4">
                        <label for="folder">Folder</label>
                        <input class="control" id="folder" type="text" name="folder" value="general" list="folder-list">
                        <datalist id="folder-list">
                            @foreach ($folders as $folder)<option value="{{ $folder }}">@endforeach
                        </datalist>
                    </div>
                    <div class="field col-2" style="justify-content:flex-end">
                        <button class="btn btn-primary" type="submit">{!! icon('upload') !!} Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="toolbar">
        <form action="{{ route('admin.media.index') }}" method="get">
            <input class="control" type="search" name="q" value="{{ request('q') }}" placeholder="Search files…">
            <select class="control" name="folder" onchange="this.form.submit()">
                <option value="">All folders</option>
                @foreach ($folders as $folder)
                    <option value="{{ $folder }}" @selected(request('folder') === $folder)>{{ $folder }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline" type="submit">{!! icon('search') !!} Filter</button>
        </form>
    </div>

    @if ($files->count())
        <div class="media-grid">
            @foreach ($files as $file)
                <div class="media-tile">
                    <div class="thumb-box">
                        @if ($file->is_image)
                            <img src="{{ uploaded_url($file->path) }}" alt="{{ $file->name }}" loading="lazy">
                        @else
                            {!! icon('document') !!}
                        @endif
                    </div>
                    <div class="meta">
                        <strong>{{ Str::limit($file->name, 34) }}</strong>
                        <span>{{ $file->folder }} · {{ $file->readable_size }}</span>
                        <span class="path">/{{ $file->path }}</span>
                    </div>
                    <div class="tile-actions">
                        <button class="btn btn-outline btn-sm" type="button" data-copy="{{ url($file->path) }}">Copy URL</button>
                        <a class="btn btn-outline btn-sm" href="{{ uploaded_url($file->path) }}" target="_blank" rel="noopener">{!! icon('eye') !!}</a>
                        <form action="{{ route('admin.media.destroy', $file) }}" method="post" data-confirm="Delete this file? Pages using it will lose the image.">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" type="submit">{!! icon('trash') !!}</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card" style="margin-top:18px">{{ $files->links('vendor.pagination.epic') }}</div>
    @else
        <div class="card"><div class="empty">{!! icon('image') !!}<p>No files uploaded yet.</p></div></div>
    @endif
@endsection

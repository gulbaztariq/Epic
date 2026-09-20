<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaLibraryController extends Controller
{
    public function index(Request $request)
    {
        $files = MediaFile::query()
            ->when($request->query('folder'), fn ($q, $folder) => $q->where('folder', $folder))
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->latest('id')
            ->paginate(24)
            ->withQueryString();

        return view('admin.media', [
            'files' => $files,
            'folders' => MediaFile::distinct()->orderBy('folder')->pluck('folder'),
        ]);
    }

    public function store(Request $request, MediaService $media)
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['required', 'file', 'max:20480'],
            'folder' => ['nullable', 'string', 'max:60'],
        ]);

        $folder = $request->input('folder') ?: 'general';

        foreach ($request->file('files') as $file) {
            $media->store($file, $folder);
        }

        return back()->with('success', count($request->file('files')).' file(s) uploaded.');
    }

    public function destroy(MediaFile $media, MediaService $service)
    {
        $service->delete($media->path);
        $media->delete();

        return back()->with('success', 'File deleted.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Services\MediaService;
use Illuminate\Http\Request;

class GalleryImageController extends Controller
{
    public function store(Request $request, GalleryAlbum $album, MediaService $media)
    {
        $request->validate([
            'images' => ['required', 'array', 'max:30'],
            'images.*' => ['required', 'image', 'max:8192'],
        ]);

        $sort = (int) $album->images()->max('sort');

        foreach ($request->file('images') as $file) {
            $album->images()->create([
                'image' => $media->store($file, 'gallery'),
                'sort' => ++$sort,
            ]);
        }

        return back()->with('success', count($request->file('images')).' photo(s) added to the album.');
    }

    public function update(Request $request, GalleryAlbum $album, GalleryImage $image)
    {
        abort_unless($image->gallery_album_id === $album->id, 404);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]);

        $image->update($data);

        return back()->with('success', 'Photo updated.');
    }

    public function destroy(GalleryAlbum $album, GalleryImage $image, MediaService $media)
    {
        abort_unless($image->gallery_album_id === $album->id, 404);

        $media->delete($image->image);
        $image->delete();

        return back()->with('success', 'Photo removed.');
    }
}

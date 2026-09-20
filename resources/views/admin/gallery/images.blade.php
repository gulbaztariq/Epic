<div class="card" style="margin-top:20px">
    <div class="card-head">
        <h3>Photos in this album ({{ $album->images->count() }})</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('admin.gallery-albums.images.store', $album) }}" method="post" enctype="multipart/form-data" style="margin-bottom:22px">
            @csrf
            <div class="form-grid">
                <div class="field col-8">
                    <label for="images">Add photos</label>
                    <input class="control" id="images" type="file" name="images[]" accept="image/*" multiple required>
                    <span class="hint">Select several files at once. Max 8 MB each.</span>
                </div>
                <div class="field col-4" style="justify-content:flex-end">
                    <button class="btn btn-green" type="submit">{!! icon('upload') !!} Upload photos</button>
                </div>
            </div>
        </form>

        @forelse ($album->images as $image)
            <form class="image-row" action="{{ route('admin.gallery-albums.images.update', [$album, $image]) }}" method="post">
                @csrf
                @method('PUT')
                <img src="{{ uploaded_url($image->image) }}" alt="">
                <input class="control" type="text" name="caption" value="{{ $image->caption }}" placeholder="Caption (optional)">
                <input class="control" type="number" name="sort" value="{{ $image->sort }}" min="0" title="Display order">
                <span style="display:flex;gap:6px">
                    <button class="btn btn-outline btn-sm" type="submit">{!! icon('check') !!} Save</button>
                </span>
            </form>
            <form action="{{ route('admin.gallery-albums.images.destroy', [$album, $image]) }}" method="post"
                  data-confirm="Remove this photo?" style="margin:-6px 0 12px">
                @csrf @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit">{!! icon('trash') !!} Remove photo</button>
            </form>
        @empty
            <div class="empty">{!! icon('image') !!}<p>No photos in this album yet.</p></div>
        @endforelse
    </div>
</div>

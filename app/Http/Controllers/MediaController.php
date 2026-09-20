<?php

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\Podcast;
use App\Models\Post;
use App\Models\Video;

class MediaController extends Controller
{
    public function press()
    {
        return view('site.media.press', [
            'page' => Page::findBySlug('press-releases'),
            'posts' => Post::published()->category('press_release')->paginate(9),
        ]);
    }

    public function pressShow(Post $post)
    {
        abort_unless($post->is_published && $post->category === 'press_release', 404);

        return view('site.posts.show', [
            'post' => $post,
            'related' => Post::published()->category('press_release')->whereKeyNot($post->id)->take(3)->get(),
        ]);
    }

    public function podcasts()
    {
        return view('site.media.podcasts', [
            'page' => Page::findBySlug('podcast'),
            'episodes' => Podcast::published()->paginate(9),
        ]);
    }

    public function podcast(Podcast $podcast)
    {
        abort_unless($podcast->is_published, 404);

        return view('site.media.podcast-show', [
            'episode' => $podcast,
            'related' => Podcast::published()->whereKeyNot($podcast->id)->take(4)->get(),
        ]);
    }

    public function videos()
    {
        $videos = Video::published()->paginate(9);

        return view('site.media.videos', [
            'page' => Page::findBySlug('youtube'),
            'videos' => $videos,
            'featured' => Video::published()->where('is_featured', true)->first() ?? $videos->first(),
        ]);
    }

    public function gallery()
    {
        return view('site.media.gallery', [
            'page' => Page::findBySlug('gallery'),
            'albums' => GalleryAlbum::published()->with('images')->paginate(12),
        ]);
    }

    public function album(GalleryAlbum $album)
    {
        abort_unless($album->is_published, 404);

        return view('site.media.album', [
            'album' => $album->load('images'),
            'more' => GalleryAlbum::published()->whereKeyNot($album->id)->take(3)->get(),
        ]);
    }
}

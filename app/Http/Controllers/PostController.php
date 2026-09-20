<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $category = in_array($request->query('category'), ['blog', 'article'], true)
            ? $request->query('category')
            : null;

        return view('site.posts.index', [
            'page' => Page::findBySlug('blogs'),
            'posts' => Post::published()
                ->category($category ? [$category] : ['blog', 'article'])
                ->paginate(9)
                ->withQueryString(),
            'activeCategory' => $category,
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->is_published && $post->category !== 'press_release', 404);

        return view('site.posts.show', [
            'post' => $post,
            'related' => Post::published()
                ->category(['blog', 'article'])
                ->whereKeyNot($post->id)
                ->take(3)
                ->get(),
        ]);
    }
}

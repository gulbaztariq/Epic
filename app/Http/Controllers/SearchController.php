<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $results = collect();

        if (Str::length($term) >= 2) {
            $like = '%'.$term.'%';

            $results = collect()
                ->concat(Publication::published()
                    ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('abstract', 'like', $like)->orWhere('body', 'like', $like))
                    ->take(12)->get()
                    ->map(fn ($p) => [
                        'title' => $p->title,
                        'type' => $p->type,
                        'summary' => summarise($p->abstract ?: $p->body, 170),
                        'url' => route('publications.show', $p->slug),
                        'date' => $p->published_at,
                    ]))
                ->concat(Post::published()
                    ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
                    ->take(12)->get()
                    ->map(fn ($p) => [
                        'title' => $p->title,
                        'type' => $p->category_label,
                        'summary' => summarise($p->summary, 170),
                        'url' => $p->publicUrl(),
                        'date' => $p->published_at,
                    ]))
                ->concat(Event::published()
                    ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('description', 'like', $like))
                    ->take(12)->get()
                    ->map(fn ($e) => [
                        'title' => $e->title,
                        'type' => 'Event',
                        'summary' => summarise($e->excerpt ?: $e->description, 170),
                        'url' => route('events.show', $e->slug),
                        'date' => $e->starts_at,
                    ]))
                ->concat(Project::published()
                    ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('summary', 'like', $like)->orWhere('description', 'like', $like))
                    ->take(12)->get()
                    ->map(fn ($p) => [
                        'title' => $p->title,
                        'type' => 'Project',
                        'summary' => summarise($p->summary ?: $p->description, 170),
                        'url' => route('work.projects.show', $p->slug),
                        'date' => $p->started_at,
                    ]));
        }

        return view('site.search', [
            'term' => $term,
            'results' => $results,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ListItem;
use App\Models\Page;
use App\Models\Publication;
use Illuminate\Http\Request;

class PublicationController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type');

        $publications = Publication::published()
            ->collection('collection')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->paginate(9)
            ->withQueryString();

        return view('site.publications.index', [
            'page' => Page::findBySlug('publications'),
            'publications' => $publications,
            'types' => Publication::published()->collection('collection')->distinct()->pluck('type')->filter()->values(),
            'activeType' => $type,
            'collectionTypes' => ListItem::inGroup('publication_types'),
        ]);
    }

    public function journal()
    {
        return view('site.publications.journal', [
            'page' => Page::findBySlug('journal'),
            'issues' => Publication::published()->collection('journal')->paginate(9),
        ]);
    }

    public function newsletter()
    {
        return view('site.publications.newsletter', [
            'page' => Page::findBySlug('newsletter'),
            'issues' => Publication::published()->collection('newsletter')->paginate(9),
        ]);
    }

    public function show(Publication $publication)
    {
        abort_unless($publication->is_published, 404);

        return view('site.publications.show', [
            'publication' => $publication,
            'related' => Publication::published()
                ->whereKeyNot($publication->id)
                ->where('collection', $publication->collection)
                ->take(4)
                ->get(),
        ]);
    }
}

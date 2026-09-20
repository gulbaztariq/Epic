<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ListItem;
use App\Models\Page;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('show', 'upcoming');

        $events = $filter === 'past'
            ? Event::past()->paginate(9)->withQueryString()
            : Event::upcoming()->paginate(9)->withQueryString();

        return view('site.events.index', [
            'page' => Page::findBySlug('events'),
            'events' => $events,
            'filter' => $filter === 'past' ? 'past' : 'upcoming',
            'eventTypes' => ListItem::inGroup('event_types'),
            'upcomingCount' => Event::upcoming()->count(),
            'pastCount' => Event::past()->count(),
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->is_published, 404);

        return view('site.events.show', [
            'event' => $event,
            'related' => Event::published()->whereKeyNot($event->id)->orderByDesc('starts_at')->take(3)->get(),
        ]);
    }
}

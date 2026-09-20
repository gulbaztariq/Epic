<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\FocusArea;
use App\Models\ListItem;
use App\Models\Page;
use App\Models\Publication;
use App\Models\Stat;

class HomeController extends Controller
{
    public function index()
    {
        $page = Page::findBySlug('home');

        return view('site.home', [
            'page' => $page,
            'focusAreas' => FocusArea::active()->get(),
            'highlights' => ListItem::inGroup('hero_highlights'),
            'pillars' => ListItem::inGroup('entrepreneurship_pillars'),
            'stats' => Stat::active()->get(),
            'publications' => Publication::published()
                ->where('is_featured', true)
                ->take(5)
                ->get()
                ->whenEmpty(fn () => Publication::published()->take(5)->get()),
            'events' => Event::upcoming()->take(3)->get()
                ->whenEmpty(fn () => Event::published()->orderByDesc('starts_at')->take(3)->get()),
        ]);
    }
}

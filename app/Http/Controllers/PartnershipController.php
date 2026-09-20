<?php

namespace App\Http\Controllers;

use App\Models\ListItem;
use App\Models\Page;
use App\Models\Partner;

class PartnershipController extends Controller
{
    public function index()
    {
        return view('site.partnerships.index', [
            'page' => Page::findBySlug('partnerships'),
            'partners' => Partner::type('partnership')->get(),
            'categories' => ListItem::inGroup('partner_types'),
        ]);
    }

    public function mous()
    {
        return view('site.partnerships.mous', [
            'page' => Page::findBySlug('mous'),
            'partners' => Partner::type('mou')->get(),
            'scope' => ListItem::inGroup('mou_scope'),
        ]);
    }

    public function memberships()
    {
        return view('site.partnerships.memberships', [
            'page' => Page::findBySlug('memberships'),
            'partners' => Partner::type('membership')->get(),
            'networks' => ListItem::inGroup('memberships'),
        ]);
    }
}

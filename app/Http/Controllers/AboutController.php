<?php

namespace App\Http\Controllers;

use App\Models\ListItem;
use App\Models\Page;
use App\Models\TeamMember;

class AboutController extends Controller
{
    public function index()
    {
        return view('site.about.index', ['page' => Page::findBySlug('about-us')]);
    }

    public function vision()
    {
        return view('site.about.vision', ['page' => Page::findBySlug('vision-mission')]);
    }

    public function principles()
    {
        return view('site.about.principles', [
            'page' => Page::findBySlug('epic-principles'),
            'principles' => ListItem::inGroup('principles'),
        ]);
    }

    public function strengths()
    {
        return view('site.about.strengths', [
            'page' => Page::findBySlug('our-strengths'),
            'strengths' => ListItem::inGroup('strengths'),
        ]);
    }

    public function team()
    {
        return view('site.about.people', [
            'page' => Page::findBySlug('epic-team'),
            'members' => TeamMember::category('team')->get(),
            'areas' => collect(),
        ]);
    }

    public function board()
    {
        return view('site.about.people', [
            'page' => Page::findBySlug('board-of-governance'),
            'members' => TeamMember::category('board')->get(),
            'areas' => ListItem::inGroup('board_areas'),
            'areasHeading' => 'The Board supports the organisation in areas including',
        ]);
    }

    public function advisory()
    {
        return view('site.about.people', [
            'page' => Page::findBySlug('advisory-council'),
            'members' => TeamMember::category('advisory')->get(),
            'areas' => ListItem::inGroup('advisory_areas'),
            'areasHeading' => 'The Advisory Council provides non-executive technical and strategic guidance on',
        ]);
    }
}

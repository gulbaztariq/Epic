<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\ListItem;
use App\Models\Page;
use App\Models\Project;

class WorkController extends Controller
{
    public function themes()
    {
        return view('site.work.themes', [
            'page' => Page::findBySlug('themes'),
            'themes' => ListItem::inGroup('themes'),
        ]);
    }

    public function projects()
    {
        return view('site.work.projects', [
            'page' => Page::findBySlug('projects'),
            'projects' => Project::published()->paginate(9),
            'projectTypes' => ListItem::inGroup('project_types'),
        ]);
    }

    public function project(Project $project)
    {
        abort_unless($project->is_published, 404);

        return view('site.work.project-show', [
            'project' => $project,
            'related' => Project::published()->whereKeyNot($project->id)->take(3)->get(),
        ]);
    }

    public function chapters()
    {
        return view('site.work.chapters', [
            'page' => Page::findBySlug('international-chapters'),
            'chapters' => Chapter::active()->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Publication;
use Illuminate\Http\Response;

class PageController extends Controller
{
    /**
     * Render any additional page the admin creates (policies, custom pages...).
     */
    public function show(Page $page)
    {
        abort_unless($page->is_published, 404);

        return view('site.page', ['page' => $page->load('activeSections')]);
    }

    public function sitemapXml(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('about.index'), 'priority' => '0.9'],
            ['loc' => route('about.vision'), 'priority' => '0.7'],
            ['loc' => route('about.principles'), 'priority' => '0.7'],
            ['loc' => route('about.strengths'), 'priority' => '0.7'],
            ['loc' => route('about.team'), 'priority' => '0.6'],
            ['loc' => route('about.board'), 'priority' => '0.6'],
            ['loc' => route('about.advisory'), 'priority' => '0.6'],
            ['loc' => route('work.themes'), 'priority' => '0.8'],
            ['loc' => route('work.projects'), 'priority' => '0.8'],
            ['loc' => route('work.chapters'), 'priority' => '0.6'],
            ['loc' => route('events.index'), 'priority' => '0.8'],
            ['loc' => route('partnerships.index'), 'priority' => '0.7'],
            ['loc' => route('partnerships.mous'), 'priority' => '0.6'],
            ['loc' => route('partnerships.memberships'), 'priority' => '0.6'],
            ['loc' => route('publications.index'), 'priority' => '0.9'],
            ['loc' => route('publications.journal'), 'priority' => '0.7'],
            ['loc' => route('publications.newsletter'), 'priority' => '0.6'],
            ['loc' => route('blogs.index'), 'priority' => '0.7'],
            ['loc' => route('involved.careers'), 'priority' => '0.6'],
            ['loc' => route('involved.volunteer'), 'priority' => '0.5'],
            ['loc' => route('involved.subscribe'), 'priority' => '0.5'],
            ['loc' => route('contact'), 'priority' => '0.7'],
            ['loc' => route('media.press'), 'priority' => '0.6'],
            ['loc' => route('media.podcast'), 'priority' => '0.5'],
            ['loc' => route('media.youtube'), 'priority' => '0.5'],
            ['loc' => route('media.gallery'), 'priority' => '0.5'],
        ]);

        foreach (Publication::published()->get() as $item) {
            $urls->push(['loc' => route('publications.show', $item->slug), 'lastmod' => $item->updated_at, 'priority' => '0.6']);
        }

        foreach (Post::published()->get() as $item) {
            $urls->push(['loc' => $item->publicUrl(), 'lastmod' => $item->updated_at, 'priority' => '0.5']);
        }

        foreach (Event::published()->get() as $item) {
            $urls->push(['loc' => route('events.show', $item->slug), 'lastmod' => $item->updated_at, 'priority' => '0.5']);
        }

        foreach (Project::published()->get() as $item) {
            $urls->push(['loc' => route('work.projects.show', $item->slug), 'lastmod' => $item->updated_at, 'priority' => '0.5']);
        }

        // The XML declaration is prepended here rather than written at the top of
        // the Blade file. Blade tokenises templates with token_get_all(), so where
        // short_open_tag is on it reads "<?xml" as a PHP open tag and stops
        // compiling the rest of the view. Inside PHP a quoted string is safe.
        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .view('site.sitemap', ['urls' => $urls])->render();

        return response($body)->header('Content-Type', 'application/xml');
    }
}

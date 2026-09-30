<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pages the website itself looks up (the home page, "Who We Are" pages, and
     * so on). Each gets a stable key so the slug and title become ordinary,
     * freely editable fields: renaming a page in the dashboard can no longer
     * make the site lose track of it.
     *
     * Key => slugs it may currently be stored under. Only the board page has
     * more than one, because it has been renamed.
     */
    private const BUILT_IN = [
        'home' => ['home'],
        'about-us' => ['about-us'],
        'vision-mission' => ['vision-mission'],
        'epic-principles' => ['epic-principles'],
        'our-strengths' => ['our-strengths'],
        'epic-team' => ['epic-team'],
        'board' => ['board-of-governance', 'board-of-directors', 'board'],
        'advisory-council' => ['advisory-council'],
        'themes' => ['themes'],
        'projects' => ['projects'],
        'international-chapters' => ['international-chapters'],
        'events' => ['events'],
        'partnerships' => ['partnerships'],
        'mous' => ['mous'],
        'memberships' => ['memberships'],
        'publications' => ['publications'],
        'journal' => ['journal'],
        'newsletter' => ['newsletter'],
        'blogs' => ['blogs'],
        'careers' => ['careers'],
        'volunteer' => ['volunteer'],
        'subscribe' => ['subscribe'],
        'contact' => ['contact'],
        'press-releases' => ['press-releases'],
        'podcast' => ['podcast'],
        'youtube' => ['youtube'],
        'gallery' => ['gallery'],
    ];

    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('key', 60)->nullable()->unique()->after('slug');
        });

        $this->backfill();
    }

    /** Give each existing built-in page its key. Public so it can be exercised on its own. */
    public function backfill(): void
    {
        foreach (self::BUILT_IN as $key => $slugs) {
            $id = DB::table('pages')->whereIn('slug', $slugs)->whereNull('key')->orderBy('id')->value('id');

            // The board page may already have been renamed in the dashboard
            // (slug included), so fall back to its title.
            if (! $id && $key === 'board') {
                $id = DB::table('pages')
                    ->whereIn('title', ['Board of Directors', 'Board of Governance'])
                    ->whereNull('key')
                    ->orderBy('id')
                    ->value('id');
            }

            if ($id) {
                DB::table('pages')->where('id', $id)->update(['key' => $key]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
    }
};

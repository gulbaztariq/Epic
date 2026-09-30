<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SEEDED_FOCUS_LINK = 'A more innovative, competitive and inclusive Pakistan';

    /**
     * Front-page clean-up.
     *
     * The three-item strip under the hero (Evidence for Policy, Ideas for People,
     * Inclusive Growth for Pakistan) was removed from the website, so its list is
     * removed too: leaving it would keep a dashboard list that changes nothing.
     *
     * The link beside "Our Focus Areas" is cleared only while it still holds the
     * wording it was created with; anything an editor has written is kept.
     */
    public function up(): void
    {
        if (Schema::hasTable('list_items')) {
            DB::table('list_items')->where('group', 'hero_highlights')->delete();
        }

        if (Schema::hasTable('page_sections')) {
            DB::table('page_sections')
                ->where('type', 'focus')
                ->where('link_text', self::SEEDED_FOCUS_LINK)
                ->update(['link_text' => null, 'link_url' => null]);
        }
    }

    /** The removed wording is not restored. */
    public function down(): void
    {
        //
    }
};

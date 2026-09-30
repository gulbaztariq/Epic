<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD = 'Board of Governance';

    private const NEW = 'Board of Directors';

    private const OLD_SLUG = 'board-of-governance';

    private const NEW_SLUG = 'board-of-directors';

    /**
     * Carry the rename into a database that already holds the old wording.
     *
     * Only the old name is replaced, wherever it still appears, so anything an
     * editor has since written in its place is left exactly as they wrote it.
     * On a fresh install these tables are empty and this does nothing; the
     * seeders already use the new name.
     */
    public function up(): void
    {
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'key')) {
            $page = DB::table('pages')->where('key', 'board')->first();

            if ($page) {
                $update = [];

                foreach (['title', 'menu_label', 'hero_title', 'hero_subtitle', 'intro', 'body', 'meta_title', 'meta_description'] as $column) {
                    if (is_string($page->{$column}) && str_contains($page->{$column}, self::OLD)) {
                        $update[$column] = str_replace(self::OLD, self::NEW, $page->{$column});
                    }
                }

                // The address moves with the name, unless the slug is taken.
                if ($page->slug === self::OLD_SLUG && ! DB::table('pages')->where('slug', self::NEW_SLUG)->exists()) {
                    $update['slug'] = self::NEW_SLUG;
                }

                if ($update) {
                    DB::table('pages')->where('id', $page->id)->update($update);
                }
            }
        }

        if (Schema::hasTable('menu_items')) {
            DB::table('menu_items')->where('label', self::OLD)->update(['label' => self::NEW]);

            DB::table('menu_items')
                ->where('url', 'like', '%/who-we-are/'.self::OLD_SLUG)
                ->get(['id', 'url'])
                ->each(fn ($item) => DB::table('menu_items')
                    ->where('id', $item->id)
                    ->update(['url' => str_replace('/who-we-are/'.self::OLD_SLUG, '/who-we-are/'.self::NEW_SLUG, $item->url)]));
        }
    }

    /** The old wording is not restored: it cannot be told apart from later edits. */
    public function down(): void
    {
        //
    }
};

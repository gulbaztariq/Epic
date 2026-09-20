<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $header = [
            ['Who We Are', '/who-we-are', [
                ['About Us', '/who-we-are'],
                ['Vision & Mission', '/who-we-are/vision-mission'],
                ['EPIC Principles', '/who-we-are/epic-principles'],
                ['Our Strengths', '/who-we-are/our-strengths'],
                ['EPIC Team', '/who-we-are/epic-team'],
                ['Board of Governance', '/who-we-are/board-of-governance'],
                ['Advisory Council', '/who-we-are/advisory-council'],
            ]],
            ['What We Do', '/what-we-do/themes', [
                ['Themes of EPIC Work', '/what-we-do/themes'],
                ['Projects', '/what-we-do/projects'],
                ['International Chapters', '/what-we-do/international-chapters'],
            ]],
            ['Events', '/events', []],
            ['Partnerships & MoUs', '/partnerships', [
                ['Partnerships', '/partnerships'],
                ['MoUs', '/partnerships/mous'],
                ['Memberships', '/partnerships/memberships'],
            ]],
            ['Publications', '/publications', [
                ['Our Collection', '/publications'],
                ['Journal (HEC-recognized)', '/publications/journal'],
                ['E-Newsletter', '/publications/e-newsletter'],
                ['Blogs & Articles', '/blogs-and-articles'],
            ]],
            ['Get Involved', '/get-involved/careers', [
                ['Careers', '/get-involved/careers'],
                ['Volunteer', '/get-involved/volunteer'],
                ['Subscribe', '/get-involved/subscribe'],
                ['Contact', '/contact'],
            ]],
            ['Media', '/media/press-releases', [
                ['Press Release', '/media/press-releases'],
                ['Pod Cast', '/media/podcast'],
                ['YouTube', '/media/youtube'],
                ['Gallery', '/media/gallery'],
            ]],
        ];

        foreach ($header as $i => [$label, $url, $children]) {
            $parent = MenuItem::updateOrCreate(
                ['label' => $label, 'location' => 'header', 'parent_id' => null],
                ['url' => $url, 'sort' => $i + 1, 'is_active' => true]
            );

            foreach ($children as $j => [$childLabel, $childUrl]) {
                MenuItem::updateOrCreate(
                    ['label' => $childLabel, 'location' => 'header', 'parent_id' => $parent->id],
                    ['url' => $childUrl, 'sort' => $j + 1, 'is_active' => true]
                );
            }
        }

        $footer = [
            ['Explore', '#', [
                ['About EPIC', '/who-we-are'],
                ['Themes of our work', '/what-we-do/themes'],
                ['Projects', '/what-we-do/projects'],
                ['Publications', '/publications'],
                ['Events', '/events'],
            ]],
            ['Engage', '#', [
                ['Partnerships & MoUs', '/partnerships'],
                ['Careers', '/get-involved/careers'],
                ['Volunteer', '/get-involved/volunteer'],
                ['Subscribe', '/get-involved/subscribe'],
                ['Contact', '/contact'],
            ]],
        ];

        foreach ($footer as $i => [$label, $url, $children]) {
            $parent = MenuItem::updateOrCreate(
                ['label' => $label, 'location' => 'footer', 'parent_id' => null],
                ['url' => $url, 'sort' => $i + 1, 'is_active' => true]
            );

            foreach ($children as $j => [$childLabel, $childUrl]) {
                MenuItem::updateOrCreate(
                    ['label' => $childLabel, 'location' => 'footer', 'parent_id' => $parent->id],
                    ['url' => $childUrl, 'sort' => $j + 1, 'is_active' => true]
                );
            }
        }

        $legal = [
            ['Privacy Policy', '/p/privacy-policy'],
            ['Terms of Use', '/p/terms-of-use'],
            ['Sitemap', '/sitemap.xml'],
        ];

        foreach ($legal as $i => [$label, $url]) {
            MenuItem::updateOrCreate(
                ['label' => $label, 'location' => 'footer_legal', 'parent_id' => null],
                ['url' => $url, 'sort' => $i + 1, 'is_active' => true]
            );
        }

    }
}

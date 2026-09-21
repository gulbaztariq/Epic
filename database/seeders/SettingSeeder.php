<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            'site_name' => ['EPIC', 'general', 1],
            'site_name_full' => ['Economic Policy and Innovation Centre (EPIC)', 'general', 2],
            'site_description' => ['EPIC is an independent policy, research and knowledge institution advancing evidence-based solutions for economic prosperity, human capital development, accountable governance, entrepreneurship and responsible innovation in Pakistan and globally.', 'general', 3],
            'header_cta_label' => ['Support Our Work', 'general', 4],
            'header_cta_url' => ['/contact', 'general', 5],

            // Home
            'home_cta2_label' => ['About EPIC', 'home', 1],
            'home_cta2_url' => ['/who-we-are', 'home', 2],
            'subscribe_title' => ['Stay informed', 'home', 3],
            'subscribe_text' => ['Get EPIC research, policy briefs, events and opportunities delivered to your inbox.', 'home', 4],
            'subscribe_privacy_note' => ['We use your details only to send EPIC research, events and updates. You can unsubscribe at any time.', 'home', 5],

            // Contact
            'contact_address' => ['Islamabad, Pakistan', 'contact', 1],
            'contact_email' => ['info@epic.org.pk', 'contact', 2],
            'contact_phone' => ['+92 51 1124 567', 'contact', 3],
            'office_hours' => ['Monday to Friday, 9:00 – 17:00 (PKT)', 'contact', 4],
            'notification_email' => ['info@epic.org.pk', 'contact', 5],
            'careers_email' => ['careers@epic.org.pk', 'contact', 6],
            'media_email' => ['media@epic.org.pk', 'contact', 7],

            // Footer
            'footer_about' => ['Independent research. Practical solutions. A more prosperous Pakistan.', 'footer', 1],
            'footer_tagline' => ["Ideas\nPeople\nProsperity", 'footer', 2],
            'footer_rights' => ['All rights reserved.', 'footer', 3],

            // Analytics
            'analytics_enabled' => ['1', 'analytics', 1],
            'analytics_track_bots' => ['1', 'analytics', 2],
            'analytics_geolocation' => ['1', 'analytics', 3],
            'analytics_store_full_ip' => ['0', 'analytics', 4],
            'analytics_respect_dnt' => ['0', 'analytics', 5],
            'analytics_retention_days' => ['365', 'analytics', 6],
            'show_visitor_counter' => ['1', 'analytics', 7],
            'visitor_counter_metric' => ['visitors', 'analytics', 8],
            'visitor_counter_label' => ['Website visitors', 'analytics', 9],

            // Social (left blank until the organisation shares its handles)
            'social_linkedin' => ['', 'social', 1],
            'social_x' => ['', 'social', 2],
            'social_youtube' => ['', 'social', 3],
            'social_facebook' => ['', 'social', 4],
            'social_instagram' => ['', 'social', 5],
        ];

        foreach ($settings as $key => [$value, $group, $sort]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'sort' => $sort] + (Setting::where('key', $key)->exists() ? [] : ['value' => $value])
            );
        }

        Setting::flush();
    }
}

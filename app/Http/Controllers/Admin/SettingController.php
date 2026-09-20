<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MediaService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Every editable site setting, grouped into the dashboard's tabs.
     */
    public static function schema(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'icon' => 'gear',
                'fields' => [
                    'site_name' => ['label' => 'Site name', 'type' => 'text', 'col' => 6],
                    'site_name_full' => ['label' => 'Full organisation name', 'type' => 'text', 'col' => 6, 'hint' => 'Used in the footer copyright line.'],
                    'site_description' => ['label' => 'Site description', 'type' => 'textarea', 'col' => 12, 'hint' => 'Shown to search engines when a page has no description of its own.'],
                    'logo' => ['label' => 'Logo (dark version)', 'type' => 'image', 'col' => 4, 'hint' => 'Used in the header. Leave empty to use the built-in EPIC logo.'],
                    'logo_light' => ['label' => 'Logo (light version)', 'type' => 'image', 'col' => 4, 'hint' => 'Used on dark backgrounds such as the footer.'],
                    'favicon' => ['label' => 'Favicon', 'type' => 'image', 'col' => 4, 'hint' => 'Square PNG, 512×512px recommended.'],
                    'header_cta_label' => ['label' => 'Header button label', 'type' => 'text', 'col' => 6, 'placeholder' => 'Support Our Work'],
                    'header_cta_url' => ['label' => 'Header button link', 'type' => 'text', 'col' => 6, 'placeholder' => '/contact'],
                ],
            ],
            'home' => [
                'label' => 'Home page',
                'icon' => 'grid',
                'fields' => [
                    'home_cta2_label' => ['label' => 'Second hero button label', 'type' => 'text', 'col' => 6],
                    'home_cta2_url' => ['label' => 'Second hero button link', 'type' => 'text', 'col' => 6],
                    'subscribe_title' => ['label' => 'Subscribe band heading', 'type' => 'text', 'col' => 6],
                    'subscribe_text' => ['label' => 'Subscribe band text', 'type' => 'textarea', 'col' => 6],
                    'subscribe_privacy_note' => ['label' => 'Subscribe privacy note', 'type' => 'textarea', 'col' => 12],
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'icon' => 'phone',
                'fields' => [
                    'contact_address' => ['label' => 'Address', 'type' => 'text', 'col' => 6],
                    'contact_email' => ['label' => 'Public email', 'type' => 'text', 'col' => 6],
                    'contact_phone' => ['label' => 'Phone', 'type' => 'text', 'col' => 6],
                    'office_hours' => ['label' => 'Office hours', 'type' => 'text', 'col' => 6],
                    'notification_email' => ['label' => 'Send form notifications to', 'type' => 'text', 'col' => 6, 'hint' => 'Contact and volunteer forms are emailed here (when mail is configured). Submissions are always saved in the dashboard.'],
                    'careers_email' => ['label' => 'Careers email', 'type' => 'text', 'col' => 3],
                    'media_email' => ['label' => 'Media email', 'type' => 'text', 'col' => 3],
                    'map_embed' => ['label' => 'Map embed code', 'type' => 'textarea', 'col' => 12, 'hint' => 'Paste the &lt;iframe&gt; embed code from Google Maps.'],
                ],
            ],
            'social' => [
                'label' => 'Social media',
                'icon' => 'network',
                'fields' => [
                    'social_linkedin' => ['label' => 'LinkedIn URL', 'type' => 'text', 'col' => 6],
                    'social_x' => ['label' => 'X (Twitter) URL', 'type' => 'text', 'col' => 6],
                    'social_youtube' => ['label' => 'YouTube URL', 'type' => 'text', 'col' => 6],
                    'social_facebook' => ['label' => 'Facebook URL', 'type' => 'text', 'col' => 6],
                    'social_instagram' => ['label' => 'Instagram URL', 'type' => 'text', 'col' => 6],
                ],
            ],
            'footer' => [
                'label' => 'Footer',
                'icon' => 'layers',
                'fields' => [
                    'footer_about' => ['label' => 'Footer intro text', 'type' => 'textarea', 'col' => 6],
                    'footer_tagline' => ['label' => 'Footer tagline', 'type' => 'textarea', 'col' => 6, 'hint' => 'Each line is shown on its own row, e.g. Ideas / People / Prosperity.'],
                    'footer_rights' => ['label' => 'Rights statement', 'type' => 'text', 'col' => 12],
                ],
            ],
            'integrations' => [
                'label' => 'Integrations',
                'icon' => 'cpu',
                'fields' => [
                    'head_code' => ['label' => 'Code before &lt;/head&gt;', 'type' => 'textarea', 'col' => 12, 'hint' => 'Analytics or verification tags. Paste the full script tag.'],
                    'body_code' => ['label' => 'Code before &lt;/body&gt;', 'type' => 'textarea', 'col' => 12, 'hint' => 'Chat widgets or tracking pixels.'],
                ],
            ],
        ];
    }

    public function edit(?string $group = null)
    {
        $schema = static::schema();
        $group = $group && isset($schema[$group]) ? $group : array_key_first($schema);

        return view('admin.settings', [
            'schema' => $schema,
            'group' => $group,
            'fields' => $schema[$group]['fields'],
        ]);
    }

    public function update(Request $request, string $group, MediaService $media)
    {
        $schema = static::schema();
        abort_unless(isset($schema[$group]), 404);

        $fields = $schema[$group]['fields'];

        $request->validate(collect($fields)
            ->mapWithKeys(fn ($meta, $key) => [
                $key => $meta['type'] === 'image' ? ['nullable', 'image', 'max:4096'] : ['nullable', 'string', 'max:5000'],
            ])
            ->all());

        foreach ($fields as $key => $meta) {
            if ($meta['type'] === 'image') {
                if ($request->hasFile($key)) {
                    $media->delete(Setting::get($key));
                    Setting::put($key, $media->store($request->file($key), 'branding'));
                } elseif ($request->boolean('remove_'.$key)) {
                    $media->delete(Setting::get($key));
                    Setting::put($key, null);
                }

                continue;
            }

            Setting::put($key, $request->input($key));
        }

        Setting::flush();

        return redirect()
            ->route('admin.settings', $group)
            ->with('success', $schema[$group]['label'].' settings saved.');
    }
}

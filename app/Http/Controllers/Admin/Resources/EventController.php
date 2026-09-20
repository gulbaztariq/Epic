<?php

namespace App\Http\Controllers\Admin\Resources;

use App\Http\Controllers\Admin\ResourceController;
use App\Models\Event;
use Illuminate\Validation\Rule;

class EventController extends ResourceController
{
    protected string $model = Event::class;

    protected string $uri = 'events';

    protected string $title = 'Events';

    protected string $singular = 'Event';

    protected string $description = 'Policy dialogues, roundtables, seminars, webinars and launches.';

    protected string $icon = 'calendar';

    protected string $uploadFolder = 'events';

    protected array $searchable = ['title', 'excerpt', 'location', 'city'];

    protected string $orderBy = 'starts_at';

    protected function filters(): array
    {
        return ['mode' => ['label' => 'formats', 'options' => Event::MODES]];
    }

    protected function columns(): array
    {
        return [
            ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
            ['key' => 'title', 'label' => 'Event', 'type' => 'title', 'sub' => 'excerpt'],
            ['key' => 'starts_at', 'label' => 'Starts', 'type' => 'datetime'],
            ['key' => 'city', 'label' => 'City'],
            ['key' => 'mode', 'label' => 'Format', 'type' => 'badge'],
            ['key' => 'is_published', 'label' => 'Status', 'type' => 'boolean'],
        ];
    }

    protected function fields(): array
    {
        return [
            self::section('Event details'),
            self::field('title', 'Title', 'text', ['rules' => 'required|string|max:255', 'col' => 8]),
            self::field('slug', 'Slug', 'text', [
                'col' => 4,
                'rules' => fn (?Event $r) => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('events', 'slug')->ignore($r?->id)],
            ]),
            self::field('event_type', 'Event type', 'text', ['col' => 6, 'placeholder' => 'Policy Dialogue']),
            self::field('mode', 'Format', 'select', ['col' => 6, 'options' => Event::MODES, 'default' => 'In-Person', 'rules' => 'required|string|max:40']),
            self::field('starts_at', 'Starts at', 'datetime', ['col' => 6]),
            self::field('ends_at', 'Ends at', 'datetime', ['col' => 6]),
            self::field('city', 'City', 'text', ['col' => 6, 'placeholder' => 'Islamabad']),
            self::field('location', 'Venue', 'text', ['col' => 6]),

            self::section('Content'),
            self::field('excerpt', 'Short summary', 'textarea', ['col' => 12, 'rows' => 3, 'rules' => 'nullable|string|max:600']),
            self::field('description', 'Full description', 'richtext', ['col' => 12]),
            self::field('image', 'Event image', 'image', ['col' => 6]),
            self::field('registration_url', 'Registration link', 'text', ['col' => 6]),

            self::section('Visibility'),
            self::field('is_featured', 'Featured', 'checkbox', ['col' => 6]),
            self::field('is_published', 'Published', 'checkbox', ['col' => 6, 'default' => true]),
        ];
    }
}

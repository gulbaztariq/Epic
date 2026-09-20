<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    protected $fillable = [
        'page_id', 'type', 'heading', 'subheading', 'body', 'image', 'icon',
        'link_text', 'link_url', 'list_group', 'sort', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Items for a section that renders a managed content list.
     */
    public function items()
    {
        return $this->list_group
            ? ListItem::inGroup($this->list_group)
            : collect();
    }
}

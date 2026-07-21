<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageRevision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'xms_page_revisions';

    protected $fillable = [
        'page_id',
        'title',
        'slug',
        'blocks',
        'seo',
        'author_type',
        'author_id',
    ];

    protected $casts = [
        'blocks' => 'array',
        'seo' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}

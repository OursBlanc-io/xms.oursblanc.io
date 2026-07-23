<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $table = 'xms_categories';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'xms_page_category');
    }
}

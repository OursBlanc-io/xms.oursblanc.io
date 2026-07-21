<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationGroup extends Model
{
    protected $table = 'xms_translation_groups';

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'translation_group_id');
    }
}

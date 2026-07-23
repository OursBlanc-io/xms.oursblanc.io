<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $table = 'xms_forms';

    protected $fillable = [
        'name',
        'slug',
        'success_message',
        'submit_label',
        'notification_emails',
        'webhook_url',
        'webhook_enabled',
    ];

    protected $casts = [
        'notification_emails' => 'array',
        'webhook_enabled' => 'boolean',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->latest('created_at');
    }
}

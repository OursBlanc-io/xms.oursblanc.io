<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $table = 'xms_form_submissions';

    protected $fillable = [
        'form_id',
        'data',
        'ip_address',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}

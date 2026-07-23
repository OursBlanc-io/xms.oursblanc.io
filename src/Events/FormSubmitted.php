<?php

namespace OursBlanc\Xms\Events;

use OursBlanc\Xms\Models\FormSubmission;

class FormSubmitted
{
    public function __construct(public FormSubmission $submission) {}
}

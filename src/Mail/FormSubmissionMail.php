<?php

namespace OursBlanc\Xms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use OursBlanc\Xms\Models\FormSubmission;

class FormSubmissionMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public FormSubmission $submission) {}

    public function build(): self
    {
        return $this->subject("New submission: {$this->submission->form->name}")
            ->view('xms::mail.form-submission', [
                'form' => $this->submission->form,
                'data' => $this->submission->data,
            ]);
    }
}

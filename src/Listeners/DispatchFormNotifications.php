<?php

namespace OursBlanc\Xms\Listeners;

use Illuminate\Support\Facades\Mail;
use OursBlanc\Xms\Events\FormSubmitted;
use OursBlanc\Xms\Jobs\DispatchFormWebhookJob;
use OursBlanc\Xms\Mail\FormSubmissionMail;

class DispatchFormNotifications
{
    public function handle(FormSubmitted $event): void
    {
        $form = $event->submission->form;

        $emails = $form->notification_emails ?? [];

        if ($emails !== []) {
            Mail::to($emails)->queue(new FormSubmissionMail($event->submission));
        }

        if ($form->webhook_enabled && $form->webhook_url) {
            DispatchFormWebhookJob::dispatch($event->submission);
        }
    }
}

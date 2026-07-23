<?php

namespace OursBlanc\Xms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OursBlanc\Xms\Models\FormSubmission;

class DispatchFormWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public FormSubmission $submission) {}

    public function handle(): void
    {
        $form = $this->submission->form;

        if (! $form->webhook_enabled || ! $form->webhook_url) {
            return;
        }

        $response = Http::timeout(10)->post($form->webhook_url, [
            'form' => ['id' => $form->id, 'name' => $form->name, 'slug' => $form->slug],
            'submission' => [
                'id' => $this->submission->id,
                'data' => $this->submission->data,
                'submitted_at' => $this->submission->created_at->toAtomString(),
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('xms: form webhook delivery failed.', [
                'form_id' => $form->id,
                'webhook_url' => $form->webhook_url,
                'status' => $response->status(),
            ]);
        }
    }
}

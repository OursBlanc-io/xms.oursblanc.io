<?php

namespace OursBlanc\Xms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OursBlanc\Xms\Models\FormSubmission;

/*
 * Delivers a submission to the form's webhook.
 *
 * Two things this job deliberately does NOT do.
 *
 * It never reads the HTTP request. Everything the consumer needs about the
 * visitor was captured by FormSubmissionController and stored on the
 * submission: a queued job has no request, so a cookie read from here would be
 * null in production and correct in a synchronous test, which is the worst
 * kind of bug. See the migration that added those columns.
 *
 * It never sends an unsigned payload to a form that has a secret. The receiver
 * creates records from what it gets, so the URL alone cannot be the credential.
 */
class DispatchFormWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /*
     * Three attempts, spread out. A submission is a lead: losing one because
     * the consumer happened to be restarting is not acceptable, and the
     * receiving end is expected to be idempotent on the submission id.
     */
    public int $tries = 3;

    public array $backoff = [30, 300];

    public function __construct(public FormSubmission $submission) {}

    public function handle(): void
    {
        $form = $this->submission->form;

        if (! $form->webhook_enabled || ! $form->webhook_url) {
            return;
        }

        /*
         * The field schema travels with the payload so the consumer can find
         * the email, or any other field, by TYPE rather than by guessing at a
         * key name. A tacit naming convention between two repositories breaks
         * the first time someone renames a field in the admin.
         */
        $body = json_encode([
            'form' => [
                'id' => $form->id,
                'name' => $form->name,
                'slug' => $form->slug,
                'fields' => $form->fields
                    ->map(fn ($field) => [
                        'key' => $field->key,
                        'label' => $field->label,
                        'type' => $field->type,
                    ])
                    ->values()
                    ->all(),
            ],
            'submission' => [
                'id' => $this->submission->id,
                'data' => $this->submission->data,
                'ip_address' => $this->submission->ip_address,
                'visitor_id' => $this->submission->visitor_id,
                'page_url' => $this->submission->page_url,
                'user_agent' => $this->submission->user_agent,
                'submitted_at' => $this->submission->created_at->toAtomString(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $request = Http::timeout(10)
            ->withBody($body, 'application/json')
            ->withHeaders($this->signatureHeaders($form->webhook_secret, $body));

        $response = $request->post($form->webhook_url);

        if (! $response->successful()) {
            Log::warning('xms: form webhook delivery failed.', [
                'form_id' => $form->id,
                'submission_id' => $this->submission->id,
                'webhook_url' => $form->webhook_url,
                'status' => $response->status(),
            ]);

            // Raise so the retries above actually happen. Without this the job
            // would report success and the lead would be lost silently.
            $response->throw();
        }
    }

    /*
     * The timestamp is signed together with the body, and sent alongside it, so
     * the receiver can reject a replay without having to remember anything.
     *
     * The RAW body is what gets signed, never the decoded array: the two ends
     * do not have to agree on key order, escaping or float formatting for the
     * comparison to hold.
     */
    private function signatureHeaders(?string $secret, string $body): array
    {
        if (blank($secret)) {
            return [];
        }

        $timestamp = (string) now()->getTimestamp();

        return [
            'X-Xms-Timestamp' => $timestamp,
            'X-Xms-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret),
        ];
    }
}

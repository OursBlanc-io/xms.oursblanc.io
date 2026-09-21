<?php

use Illuminate\Support\Facades\Http;
use OursBlanc\Xms\Jobs\DispatchFormWebhookJob;
use OursBlanc\Xms\Models\Form;
use OursBlanc\Xms\Models\FormSubmission;

function makeWebhookForm(array $overrides = []): Form
{
    $form = Form::create(array_merge([
        'name' => 'Contact',
        'slug' => 'contact-hook',
        'notification_emails' => [],
        'webhook_enabled' => true,
        'webhook_url' => 'https://crm.example.com/api/xms/form-submission',
        'webhook_secret' => 'a-shared-secret',
    ], $overrides));

    $form->fields()->createMany([
        ['label' => 'Name', 'key' => 'name', 'type' => 'text', 'is_required' => true, 'sort_order' => 0],
        ['label' => 'Email', 'key' => 'email', 'type' => 'email', 'is_required' => true, 'sort_order' => 1],
    ]);

    return $form;
}

/*
 * The point of this file. The context a consumer needs about the visitor is
 * captured during the request and stored, because the job that delivers it
 * runs later, on a queue, with no request at all.
 */
it('captures the visitor cookie, the page and the user agent on the submission', function () {
    $form = makeWebhookForm();

    $this->withUnencryptedCookie(config('xms.forms.visitor_cookie'), 'c0ffee00-1111-4222-8333-444455556666.abcdef0123456789abcd')
        ->withHeaders([
            'Referer' => 'https://www.oursblanc.io/fr/tarifs?utm_source=linkedin',
            'User-Agent' => 'Mozilla/5.0 (Macintosh)',
        ])
        ->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'alice@acme.com']);

    expect(FormSubmission::sole())
        ->visitor_id->toBe('c0ffee00-1111-4222-8333-444455556666.abcdef0123456789abcd')
        ->page_url->toBe('https://www.oursblanc.io/fr/tarifs?utm_source=linkedin')
        ->user_agent->toBe('Mozilla/5.0 (Macintosh)');
});

it('ignores a visitor cookie whose shape is not plausible', function () {
    $form = makeWebhookForm();

    $this->withUnencryptedCookie(config('xms.forms.visitor_cookie'), '<script>alert(1)</script>')
        ->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'alice@acme.com']);

    expect(FormSubmission::sole()->visitor_id)->toBeNull();
});

it('records no visitor when the cookie is absent', function () {
    $form = makeWebhookForm();

    $this->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'alice@acme.com']);

    expect(FormSubmission::sole()->visitor_id)->toBeNull();
});

/*
 * Runs the job on its own, outside any request, which is how it runs in
 * production. A payload that only carries the visitor when a request happens
 * to be around would pass every other test in this file.
 */
it('sends the stored context in a payload built without a request', function () {
    Http::fake();

    $form = makeWebhookForm();

    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Alice', 'email' => 'alice@acme.com'],
        'ip_address' => '203.0.113.7',
        'visitor_id' => 'c0ffee00-1111-4222-8333-444455556666.abcdef0123456789abcd',
        'page_url' => 'https://www.oursblanc.io/fr/tarifs',
        'user_agent' => 'Mozilla/5.0 (Macintosh)',
    ]);

    (new DispatchFormWebhookJob($submission))->handle();

    Http::assertSent(function ($request) use ($submission) {
        $payload = $request->data();

        return $payload['submission']['id'] === $submission->id
            && $payload['submission']['visitor_id'] === 'c0ffee00-1111-4222-8333-444455556666.abcdef0123456789abcd'
            && $payload['submission']['page_url'] === 'https://www.oursblanc.io/fr/tarifs'
            && $payload['submission']['ip_address'] === '203.0.113.7';
    });
});

it('sends the field schema so the consumer can find a field by type', function () {
    Http::fake();

    $form = makeWebhookForm();

    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Alice', 'email' => 'alice@acme.com'],
    ]);

    (new DispatchFormWebhookJob($submission))->handle();

    Http::assertSent(function ($request) {
        $fields = collect($request->data()['form']['fields']);

        return $fields->firstWhere('type', 'email')['key'] === 'email';
    });
});

it('signs the raw body together with the timestamp', function () {
    Http::fake();

    $form = makeWebhookForm();

    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Alice', 'email' => 'alice@acme.com'],
    ]);

    (new DispatchFormWebhookJob($submission))->handle();

    Http::assertSent(function ($request) {
        $timestamp = $request->header('X-Xms-Timestamp')[0];
        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), 'a-shared-secret');

        return $request->header('X-Xms-Signature')[0] === $expected;
    });
});

it('sends no signature header when the form has no secret', function () {
    Http::fake();

    $form = makeWebhookForm(['webhook_secret' => null]);

    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Alice', 'email' => 'alice@acme.com'],
    ]);

    (new DispatchFormWebhookJob($submission))->handle();

    Http::assertSent(fn ($request) => $request->header('X-Xms-Signature') === []);
});

/*
 * A submission is a lead. A consumer that is restarting must not cost us one,
 * so a failed delivery has to raise for the retries to happen.
 */
it('raises on a failed delivery so the job is retried', function () {
    Http::fake(['*' => Http::response('nope', 500)]);

    $form = makeWebhookForm();

    $submission = FormSubmission::create([
        'form_id' => $form->id,
        'data' => ['name' => 'Alice', 'email' => 'alice@acme.com'],
    ]);

    expect(fn () => (new DispatchFormWebhookJob($submission))->handle())
        ->toThrow(Illuminate\Http\Client\RequestException::class);
});

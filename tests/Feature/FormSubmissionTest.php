<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use OursBlanc\Xms\Jobs\DispatchFormWebhookJob;
use OursBlanc\Xms\Mail\FormSubmissionMail;
use OursBlanc\Xms\Models\Form;
use OursBlanc\Xms\Models\FormSubmission;

function makeContactForm(array $overrides = []): Form
{
    $form = Form::create(array_merge([
        'name' => 'Contact',
        'slug' => 'contact',
        'notification_emails' => [],
        'webhook_enabled' => false,
    ], $overrides));

    $form->fields()->createMany([
        ['label' => 'Name', 'key' => 'name', 'type' => 'text', 'is_required' => true, 'sort_order' => 0],
        ['label' => 'Email', 'key' => 'email', 'type' => 'email', 'is_required' => true, 'sort_order' => 1],
        ['label' => 'Message', 'key' => 'message', 'type' => 'textarea', 'is_required' => false, 'sort_order' => 2],
    ]);

    return $form;
}

it('stores a valid submission and redirects back with a success message', function () {
    $form = makeContactForm();

    $response = $this->post(route('xms.forms.submit', $form), [
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'message' => 'Hello',
    ]);

    $response->assertRedirect()->assertSessionHas('xms_form_success');

    expect(FormSubmission::sole())
        ->form_id->toBe($form->id)
        ->data->toBe(['name' => 'Alice', 'email' => 'alice@example.com', 'message' => 'Hello']);
});

it('rejects a submission missing a required field', function () {
    $form = makeContactForm();

    $this->post(route('xms.forms.submit', $form), ['email' => 'alice@example.com'])
        ->assertSessionHasErrors(['name']);

    expect(FormSubmission::query()->count())->toBe(0);
});

it('rejects an invalid email', function () {
    $form = makeContactForm();

    $this->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['email']);
});

it('silently drops a submission whose honeypot field is filled in', function () {
    $form = makeContactForm();

    $this->post(route('xms.forms.submit', $form), [
        'name' => 'Bot', 'email' => 'bot@example.com',
        config('xms.forms.honeypot_field') => 'i am a bot',
    ])->assertRedirect();

    expect(FormSubmission::query()->count())->toBe(0);
});

it('queues a notification email to every configured address on submission', function () {
    Mail::fake();

    $form = makeContactForm(['notification_emails' => ['owner@example.com', 'team@example.com']]);

    $this->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'alice@example.com']);

    Mail::assertQueued(FormSubmissionMail::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->hasTo('team@example.com')
    );
});

it('does not send an email when no notification address is configured', function () {
    Mail::fake();

    $form = makeContactForm(['notification_emails' => []]);

    $this->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'alice@example.com']);

    Mail::assertNothingQueued();
});

it('dispatches the webhook job when webhooks are enabled', function () {
    Queue::fake();

    $form = makeContactForm(['webhook_enabled' => true, 'webhook_url' => 'https://example.com/hook']);

    $this->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'alice@example.com']);

    Queue::assertPushed(DispatchFormWebhookJob::class, fn ($job) => $job->submission->form_id === $form->id);
});

it('does not dispatch the webhook job when webhooks are disabled', function () {
    Queue::fake();

    $form = makeContactForm(['webhook_enabled' => false]);

    $this->post(route('xms.forms.submit', $form), ['name' => 'Alice', 'email' => 'alice@example.com']);

    Queue::assertNotPushed(DispatchFormWebhookJob::class);
});

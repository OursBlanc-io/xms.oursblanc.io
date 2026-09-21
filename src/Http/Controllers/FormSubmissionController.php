<?php

namespace OursBlanc\Xms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OursBlanc\Xms\Events\FormSubmitted;
use OursBlanc\Xms\Models\Form;
use OursBlanc\Xms\Models\FormSubmission;

class FormSubmissionController
{
    public function __invoke(Request $request, Form $form): RedirectResponse
    {
        // Bots that fill in the hidden honeypot are silently dropped instead
        // of shown a validation error, so they don't learn to skip it.
        if ($request->filled(config('xms.forms.honeypot_field'))) {
            return back();
        }

        $rules = [];

        foreach ($form->fields as $field) {
            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            $fieldRules[] = match ($field->type) {
                'email' => 'email',
                'checkbox' => 'boolean',
                'select' => 'string',
                default => 'string',
            };

            $rules[$field->key] = $fieldRules;
        }

        $data = $request->validate($rules);

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'data' => $data,
            'ip_address' => $request->ip(),
            /*
             * Captured HERE and stored, not read later from the job.
             *
             * DispatchFormWebhookJob runs on a queue and has no HTTP request:
             * reading the cookie or the referer from there would return null
             * in production while working in a synchronous test. Storing them
             * also makes the webhook replayable with an identical payload.
             */
            'visitor_id' => $this->visitorId($request),
            'page_url' => Str::limit((string) $request->headers->get('referer'), 2000, ''),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
        ]);

        event(new FormSubmitted($submission));

        return back()->with('xms_form_success', $form->success_message ?? 'Thank you, your submission was received.');
    }

    /*
     * The visitor cookie is set by another application, so nothing here can
     * check that its signature is genuine: that is the consumer's job. We only
     * make sure a hostile value cannot travel any further, by keeping it to a
     * conservative character set and length.
     */
    private function visitorId(Request $request): ?string
    {
        $name = config('xms.forms.visitor_cookie');

        if (blank($name)) {
            return null;
        }

        $value = (string) $request->cookie($name);

        return preg_match('/^[A-Za-z0-9.\-_]{1,64}$/', $value) === 1 ? $value : null;
    }
}

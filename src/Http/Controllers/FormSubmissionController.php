<?php

namespace OursBlanc\Xms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);

        event(new FormSubmitted($submission));

        return back()->with('xms_form_success', $form->success_message ?? 'Thank you, your submission was received.');
    }
}

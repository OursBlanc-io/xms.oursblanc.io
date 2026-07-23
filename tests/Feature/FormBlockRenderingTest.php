<?php

use OursBlanc\Xms\Models\Form;
use OursBlanc\Xms\Models\Page;

beforeEach(function () {
    config([
        'xms.locales' => ['fr'],
        'xms.default_locale' => 'fr',
        'xms.locale_in_url' => true,
        'xms.default_locale_hidden' => true,
    ]);
});

it('renders the form fields, csrf token, and honeypot on a page', function () {
    $form = Form::create(['name' => 'Contact', 'slug' => 'contact']);
    $form->fields()->create(['label' => 'Your name', 'key' => 'name', 'type' => 'text', 'is_required' => true, 'sort_order' => 0]);

    Page::create([
        'locale' => 'fr',
        'slug' => 'contact',
        'title' => 'Contact',
        'blocks' => [['uuid' => 'f1', 'type' => 'form', 'data' => ['form_id' => $form->id]]],
        'seo' => [],
        'status' => 'published',
        'published_at' => now(),
    ]);

    $response = $this->get('/contact')->assertOk();

    $response->assertSee('Your name')
        ->assertSee('action="'.route('xms.forms.submit', $form).'"', false)
        ->assertSee('name="'.config('xms.forms.honeypot_field').'"', false)
        ->assertSee('name="_token"', false);
});

it('renders nothing meaningful when the referenced form no longer exists', function () {
    Page::create([
        'locale' => 'fr',
        'slug' => 'contact',
        'title' => 'Contact',
        'blocks' => [['uuid' => 'f1', 'type' => 'form', 'data' => ['form_id' => 999]]],
        'seo' => [],
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get('/contact')->assertOk();
});

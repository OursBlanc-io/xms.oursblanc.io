@php
    /** @var ?\OursBlanc\Xms\Models\Form $form */
    $form = $data['form'] ?? null;
@endphp
<div class="xms-block xms-block--form">
    @if (! $form)
        {{-- No form selected/found: render nothing on the public site. --}}
    @else
        @if (session('xms_form_success'))
            <p class="xms-form__success">{{ session('xms_form_success') }}</p>
        @endif

        <form method="POST" action="{{ route('xms.forms.submit', $form) }}" class="xms-form">
            @csrf

            <input type="text" name="{{ config('xms.forms.honeypot_field') }}" value="" autocomplete="off" tabindex="-1" class="xms-form__honeypot" aria-hidden="true">

            @foreach ($form->fields as $field)
                <div class="xms-form__field">
                    <label for="xms-form-{{ $field->key }}">
                        {{ $field->label }}@if($field->is_required) *@endif
                    </label>

                    @if ($field->type === 'textarea')
                        <textarea id="xms-form-{{ $field->key }}" name="{{ $field->key }}" @if($field->is_required) required @endif>{{ old($field->key) }}</textarea>
                    @elseif ($field->type === 'select')
                        <select id="xms-form-{{ $field->key }}" name="{{ $field->key }}" @if($field->is_required) required @endif>
                            @foreach (($field->options ?? []) as $option)
                                <option value="{{ $option }}" @selected(old($field->key) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->type === 'checkbox')
                        <input type="checkbox" id="xms-form-{{ $field->key }}" name="{{ $field->key }}" value="1" @checked(old($field->key))>
                    @else
                        <input type="{{ $field->type === 'email' ? 'email' : 'text' }}" id="xms-form-{{ $field->key }}" name="{{ $field->key }}" value="{{ old($field->key) }}" @if($field->is_required) required @endif>
                    @endif

                    @error($field->key)
                        <span class="xms-form__error">{{ $message }}</span>
                    @enderror
                </div>
            @endforeach

            <button type="submit" class="xms-form__submit">{{ $form->submit_label ?: __('Submit') }}</button>
        </form>
    @endif
</div>

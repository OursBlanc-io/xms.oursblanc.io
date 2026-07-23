@php
    $statePath = $getStatePath();
    $selected = $getState();
@endphp

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;">
    @forelse ($results as $photo)
        @php $isSelected = (string) $selected === (string) $photo['id']; @endphp
        <button
            type="button"
            wire:click="$set('{{ $statePath }}', {{ $photo['id'] }})"
            style="
                position: relative;
                display: block;
                width: 100%;
                aspect-ratio: 1 / 1;
                padding: 0;
                overflow: hidden;
                border-radius: 0.5rem;
                cursor: pointer;
                border: 3px solid {{ $isSelected ? '#f59e0b' : 'transparent' }};
                box-shadow: {{ $isSelected ? '0 0 0 2px rgba(245, 158, 11, 0.4)' : 'none' }};
            "
        >
            <img
                src="{{ $photo['thumbnail'] }}"
                alt="{{ $photo['photographer'] }}"
                loading="lazy"
                style="width: 100%; height: 100%; object-fit: cover; display: block;"
            >

            <span style="position: absolute; inset-inline: 0; bottom: 0; padding: 0.125rem 0.375rem; font-size: 0.7rem; color: #fff; background: rgba(0, 0, 0, 0.55); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $photo['photographer'] }}
            </span>

            @if ($isSelected)
                <span style="position: absolute; top: 0.25rem; right: 0.25rem; width: 1.5rem; height: 1.5rem; border-radius: 9999px; background: #f59e0b; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; line-height: 1;">
                    ✓
                </span>
            @endif
        </button>
    @empty
        <p style="grid-column: 1 / -1; font-size: 0.875rem; color: #6b7280;">No results.</p>
    @endforelse
</div>

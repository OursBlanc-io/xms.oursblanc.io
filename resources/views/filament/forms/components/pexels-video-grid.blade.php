@php
    $statePath = $getStatePath();
    $selected = $getState();
@endphp

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;">
    @forelse ($results as $video)
        @php $isSelected = (string) $selected === (string) $video['id']; @endphp
        <div
            style="
                position: relative;
                width: 100%;
                aspect-ratio: 1 / 1;
                overflow: hidden;
                border-radius: 0.5rem;
                border: 3px solid {{ $isSelected ? '#f59e0b' : 'transparent' }};
                box-shadow: {{ $isSelected ? '0 0 0 2px rgba(245, 158, 11, 0.4)' : 'none' }};
            "
        >
            @if ($video['preview_url'])
                <video
                    src="{{ $video['preview_url'] }}"
                    poster="{{ $video['thumbnail'] }}"
                    controls
                    muted
                    playsinline
                    preload="none"
                    style="width: 100%; height: 100%; object-fit: cover; display: block; background: #000;"
                ></video>
            @else
                <img
                    src="{{ $video['thumbnail'] }}"
                    alt="{{ $video['user'] }}"
                    loading="lazy"
                    style="width: 100%; height: 100%; object-fit: cover; display: block;"
                >
            @endif

            <span style="position: absolute; top: 0.25rem; left: 0.25rem; padding: 0.0625rem 0.375rem; border-radius: 0.25rem; font-size: 0.7rem; color: #fff; background: rgba(0, 0, 0, 0.6); pointer-events: none;">
                {{ gmdate('i:s', $video['duration']) }}
            </span>

            <span style="position: absolute; inset-inline: 0; bottom: 0; padding: 0.125rem 0.375rem; font-size: 0.7rem; color: #fff; background: rgba(0, 0, 0, 0.55); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; pointer-events: none;">
                {{ $video['user'] }}
            </span>

            <button
                type="button"
                wire:click="$set('{{ $statePath }}', {{ $video['id'] }})"
                style="
                    position: absolute;
                    bottom: 0.25rem;
                    right: 0.25rem;
                    padding: 0.125rem 0.5rem;
                    font-size: 0.7rem;
                    color: #fff;
                    background: {{ $isSelected ? '#f59e0b' : 'rgba(0, 0, 0, 0.6)' }};
                    border-radius: 0.25rem;
                    cursor: pointer;
                    border: none;
                "
            >
                {{ $isSelected ? '✓ Selected' : 'Select' }}
            </button>
        </div>
    @empty
        <p style="grid-column: 1 / -1; font-size: 0.875rem; color: #6b7280;">No results.</p>
    @endforelse
</div>

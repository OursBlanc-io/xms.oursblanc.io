@php
    use OursBlanc\Xms\Support\RevisionDiffer;
@endphp

<div class="fi-xms-revision-history space-y-3">
    @forelse($revisions as $revision)
        @php
            $diff = RevisionDiffer::diff($revision->blocks ?? [], $page->blocks ?? []);
        @endphp
        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div>
                <p class="text-sm font-medium">{{ $revision->title }}</p>
                <p class="text-xs text-gray-500">
                    {{ $revision->created_at?->diffForHumans() }}
                    &middot; {{ $revision->author_type }}#{{ $revision->author_id }}
                    &middot; +{{ count($diff['added']) }} -{{ count($diff['removed']) }} ~{{ count($diff['modified']) }}
                </p>
            </div>
            <button
                type="button"
                wire:click="restoreRevision({{ $revision->id }})"
                wire:confirm="Restore this revision? The current state will be saved as a new revision first."
                class="fi-btn fi-btn-size-sm fi-color-gray inline-flex items-center rounded-lg border px-3 py-1.5 text-sm"
            >
                Restore
            </button>
        </div>
    @empty
        <p class="text-sm text-gray-500">No revisions yet.</p>
    @endforelse
</div>

<x-filament-panels::page>
    <nav class="fi-breadcrumbs" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-1 text-sm">
            @foreach ($this->breadcrumbs() as $i => $crumb)
                <li class="flex items-center gap-1">
                    @if (! $loop->last)
                        <button
                            type="button"
                            wire:click="browseTo('{{ $crumb['path'] }}')"
                            class="text-primary-600 hover:underline dark:text-primary-400"
                        >
                            {{ $crumb['label'] }}
                        </button>
                        <span class="text-gray-400">/</span>
                    @else
                        <span class="font-medium text-gray-950 dark:text-white">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>

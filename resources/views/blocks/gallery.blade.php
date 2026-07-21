<div class="xms-block xms-block--gallery" data-columns="{{ $data['columns'] ?? 3 }}" style="--xms-gallery-columns: {{ $data['columns'] ?? 3 }}">
    @foreach($data['images'] ?? [] as $image)
        <div class="xms-block__gallery-item">
            {{-- Media rendering via <x-xms::picture> lands in Phase 4 --}}
        </div>
    @endforeach
</div>

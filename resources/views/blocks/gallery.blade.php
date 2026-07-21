<div class="xms-block xms-block--gallery" data-columns="{{ $data['columns'] ?? 3 }}" style="--xms-gallery-columns: {{ $data['columns'] ?? 3 }}">
    @foreach($data['images'] ?? [] as $image)
        <div class="xms-block__gallery-item">
            @if(!empty($image['image']))
                <x-xms::picture :media-id="$image['image']" :alt="$image['alt'] ?? ''" />
            @endif
        </div>
    @endforeach
</div>

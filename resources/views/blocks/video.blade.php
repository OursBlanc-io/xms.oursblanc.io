<div class="xms-block xms-block--video">
    <x-xms::video
        :media-id="$data['video'] ?? null"
        :poster-id="$data['poster'] ?? null"
        :url="$data['url'] ?? null"
        :autoplay="$data['autoplay'] ?? false"
        :sound="$data['sound'] ?? false"
        :controls="$data['controls'] ?? true"
        :content-fit="$data['content_fit'] ?? 'cover'"
    />
</div>

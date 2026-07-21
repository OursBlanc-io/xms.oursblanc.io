<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\SsrfGuard;

class AttachMediaFromUrlTool extends AbstractXmsTool
{
    protected string $name = 'attach_media_from_url';

    protected string $description = 'Download a file from a URL and attach it as the media for one of a block\'s '.
        'direct media fields (e.g. hero.image, video.video). Repeater-nested media fields (gallery, columns) are '.
        'not supported here — use the admin UI for those. The URL is downloaded server-side under SSRF '.
        'protections (public hosts only, no redirects, size and content-type limits).';

    /**
     * @var array<int, string>
     */
    protected array $allowedContentTypes = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'video/mp4', 'video/webm', 'video/quicktime',
    ];

    protected int $maxBytes = 10 * 1024 * 1024;

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->required(),
            'block_uuid' => $schema->string()->required(),
            'field' => $schema->string()->description('The media field name on that block, e.g. "image".')->required(),
            'url' => $schema->string()->description('http(s) URL to download.')->required(),
            'alt' => $schema->string()->description('Alt text, set on the block\'s `alt` field if present.'),
        ];
    }

    public function handle(Request $request, BlockRegistry $registry): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate([
            'page_id' => 'required|integer',
            'block_uuid' => 'required|string',
            'field' => 'required|string',
            'url' => 'required|url',
            'alt' => 'nullable|string',
        ]);

        $page = Page::find($data['page_id']);

        if (! $page) {
            return Response::error("No page with id [{$data['page_id']}].");
        }

        $blocks = $page->blocks ?? [];
        $blockIndex = collect($blocks)->search(fn (array $b) => $b['uuid'] === $data['block_uuid']);

        if ($blockIndex === false) {
            return Response::error("No block with uuid [{$data['block_uuid']}] on page [{$data['page_id']}].");
        }

        $block = $blocks[$blockIndex];
        $blockClass = $registry->find($block['type']);

        if (! $blockClass || ! in_array($data['field'], $blockClass::mediaFields(), true)) {
            return Response::error(
                "Field [{$data['field']}] is not a direct media field on block type [{$block['type']}]. ".
                'Media fields on this type: '.implode(', ', $blockClass ? $blockClass::mediaFields() : []),
            );
        }

        try {
            SsrfGuard::assertSafeUrl($data['url']);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }

        $response = Http::withOptions(['allow_redirects' => false])
            ->timeout(10)
            ->get($data['url']);

        if (! $response->successful()) {
            return Response::error("Failed to download the file (HTTP {$response->status()}).");
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));

        if (! in_array($contentType, $this->allowedContentTypes, true)) {
            return Response::error("Unsupported content type [{$contentType}].");
        }

        if (strlen($response->body()) > $this->maxBytes) {
            return Response::error('The file is larger than the 10MB limit.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'xms-mcp-media-');
        file_put_contents($tempPath, $response->body());

        $fileName = basename((string) parse_url($data['url'], PHP_URL_PATH)) ?: 'file';

        // toMediaCollection()'s $diskName must be explicit: omitted, it falls
        // back to spatie's own media-library.disk_name default, not ours.
        $media = $page->addMedia($tempPath)
            ->usingFileName($fileName)
            ->toMediaCollection("block-{$block['uuid']}", config('xms.media_disk'));

        @unlink($tempPath);

        $blocks[$blockIndex]['data'][$data['field']] = $media->id;

        if (! empty($data['alt']) && array_key_exists('alt', $blocks[$blockIndex]['data'])) {
            $blocks[$blockIndex]['data']['alt'] = $data['alt'];
        }

        $page->update(['blocks' => $blocks]);

        return Response::structured([
            'media_id' => $media->id,
            'block_uuid' => $block['uuid'],
            'field' => $data['field'],
        ]);
    }
}

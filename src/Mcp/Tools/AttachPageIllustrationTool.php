<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\SsrfGuard;

class AttachPageIllustrationTool extends AbstractXmsTool
{
    protected string $name = 'attach_page_illustration';

    protected string $description = 'Download an image from a URL and set it as a page\'s listing illustration '.
        '(the cover shown in page_list cards) — separate from any image used in the page\'s own blocks. The URL '.
        'is downloaded server-side under SSRF protections (public hosts only, no redirects, size/type limits).';

    /**
     * @var array<int, string>
     */
    protected array $allowedContentTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    protected int $maxBytes = 10 * 1024 * 1024;

    public function schema(JsonSchema $schema): array
    {
        return [
            'page_id' => $schema->integer()->required(),
            'url' => $schema->string()->description('http(s) URL to download.')->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate([
            'page_id' => 'required|integer',
            'url' => 'required|url',
        ]);

        $page = Page::find($data['page_id']);

        if (! $page) {
            return Response::error("No page with id [{$data['page_id']}].");
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

        $tempPath = tempnam(sys_get_temp_dir(), 'xms-mcp-illustration-');
        file_put_contents($tempPath, $response->body());

        $fileName = basename((string) parse_url($data['url'], PHP_URL_PATH)) ?: 'file';

        $media = $page->addMedia($tempPath)
            ->usingFileName($fileName)
            ->toMediaCollection(Page::ILLUSTRATION_COLLECTION, config('xms.media_disk'));

        @unlink($tempPath);

        return Response::structured(['media_id' => $media->id, 'url' => $media->getUrl()]);
    }
}

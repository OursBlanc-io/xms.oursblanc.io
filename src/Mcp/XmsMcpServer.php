<?php

namespace OursBlanc\Xms\Mcp;

use Laravel\Mcp\Server;
use OursBlanc\Xms\Mcp\Tools\AttachMediaFromUrlTool;
use OursBlanc\Xms\Mcp\Tools\CreatePageTool;
use OursBlanc\Xms\Mcp\Tools\GetPageTool;
use OursBlanc\Xms\Mcp\Tools\ListBlockTypesTool;
use OursBlanc\Xms\Mcp\Tools\ListPagesTool;
use OursBlanc\Xms\Mcp\Tools\PatchBlocksTool;
use OursBlanc\Xms\Mcp\Tools\PublishPageTool;
use OursBlanc\Xms\Mcp\Tools\TranslatePageTool;
use OursBlanc\Xms\Mcp\Tools\UnpublishPageTool;
use OursBlanc\Xms\Mcp\Tools\UpdatePageTool;

class XmsMcpServer extends Server
{
    protected string $name = 'XMS';

    protected string $version = '0.1.0';

    protected string $instructions = <<<'TEXT'
        Tools to create, edit, translate, and publish pages for the XMS block-based CMS.

        Always call list_block_types first: it returns every available block type together with
        its JSON Schema and media fields, so pages can be composed correctly without any external
        documentation.

        Pages are always created as drafts. Use the preview URL returned by create_page/get_page to
        review content before calling publish_page. Every write is recorded as a revision.
        TEXT;

    protected array $tools = [
        ListBlockTypesTool::class,
        ListPagesTool::class,
        GetPageTool::class,
        CreatePageTool::class,
        UpdatePageTool::class,
        PatchBlocksTool::class,
        AttachMediaFromUrlTool::class,
        TranslatePageTool::class,
        PublishPageTool::class,
        UnpublishPageTool::class,
    ];
}

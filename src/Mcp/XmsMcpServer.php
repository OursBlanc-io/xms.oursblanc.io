<?php

namespace OursBlanc\Xms\Mcp;

use Laravel\Mcp\Server;
use OursBlanc\Xms\Mcp\Tools\AttachMediaFromUrlTool;
use OursBlanc\Xms\Mcp\Tools\AttachPageIllustrationTool;
use OursBlanc\Xms\Mcp\Tools\CreateCategoryTool;
use OursBlanc\Xms\Mcp\Tools\CreateFormTool;
use OursBlanc\Xms\Mcp\Tools\CreateMenuTool;
use OursBlanc\Xms\Mcp\Tools\CreatePageTool;
use OursBlanc\Xms\Mcp\Tools\DeleteCategoryTool;
use OursBlanc\Xms\Mcp\Tools\DeleteMenuTool;
use OursBlanc\Xms\Mcp\Tools\DuplicatePageTool;
use OursBlanc\Xms\Mcp\Tools\GetCategoryTool;
use OursBlanc\Xms\Mcp\Tools\GetFormTool;
use OursBlanc\Xms\Mcp\Tools\GetMenuTool;
use OursBlanc\Xms\Mcp\Tools\GetPageTool;
use OursBlanc\Xms\Mcp\Tools\ListBlockTypesTool;
use OursBlanc\Xms\Mcp\Tools\ListCategoriesTool;
use OursBlanc\Xms\Mcp\Tools\ListFormsTool;
use OursBlanc\Xms\Mcp\Tools\ListFormSubmissionsTool;
use OursBlanc\Xms\Mcp\Tools\ListMenusTool;
use OursBlanc\Xms\Mcp\Tools\ListPagesTool;
use OursBlanc\Xms\Mcp\Tools\PatchBlocksTool;
use OursBlanc\Xms\Mcp\Tools\PublishPageTool;
use OursBlanc\Xms\Mcp\Tools\TranslatePageTool;
use OursBlanc\Xms\Mcp\Tools\UnpublishPageTool;
use OursBlanc\Xms\Mcp\Tools\UpdateCategoryTool;
use OursBlanc\Xms\Mcp\Tools\UpdateFormTool;
use OursBlanc\Xms\Mcp\Tools\UpdateMenuTool;
use OursBlanc\Xms\Mcp\Tools\UpdatePageTool;

class XmsMcpServer extends Server
{
    protected string $name = 'XMS';

    protected string $version = '0.1.0';

    protected string $instructions = <<<'TEXT'
        Tools to create, edit, translate, and publish pages for the XMS block-based CMS, and to
        manage its navigation menus.

        Always call list_block_types first: it returns every available block type together with
        its JSON Schema and media fields, so pages can be composed correctly without any external
        documentation.

        Pages are always created as drafts. Use the preview URL returned by create_page/get_page to
        review content before calling publish_page. Every write is recorded as a revision.

        Menus are identified by a (location, locale) pair, e.g. "header"/"fr" — a location has one
        menu per locale. Items are two levels deep: each item has a `label` and one of link_type
        "page" (+ page_id), "url" (+ url, also used for anchors like "#formats"), or
        "language_switch" (+ target_locale — resolves to the current page's translation in that
        locale, or its homepage, so prefer it over a hardcoded locale URL). `target` ("_self"/
        "_blank") controls new-tab opening. Top-level items may set `display` ("link",
        "button_primary", "button_secondary") to render as a button; children (dropdown entries)
        are always plain links and don't support `display`. Items may have one level of `children`.

        Forms are created with create_form (name + a list of fields) and rendered on a page via the
        `form` block, which references a form by id. Each submission can email a list of addresses
        and/or call a webhook, configured on the form itself.

        Every page also has `list_title`/`list_excerpt` (how it appears as a card in a page_list
        block, falling back to `title` when empty), an illustration (set via
        attach_page_illustration), and a freeform `meta` key/value object. A page_list block can
        filter on both a single category and any of a page's `meta` keys — set the same meta key
        (e.g. "format") consistently across the pages you want a listing to facet-filter by.
        TEXT;

    protected array $tools = [
        ListBlockTypesTool::class,
        ListPagesTool::class,
        GetPageTool::class,
        CreatePageTool::class,
        UpdatePageTool::class,
        DuplicatePageTool::class,
        PatchBlocksTool::class,
        AttachMediaFromUrlTool::class,
        AttachPageIllustrationTool::class,
        TranslatePageTool::class,
        PublishPageTool::class,
        UnpublishPageTool::class,
        ListCategoriesTool::class,
        GetCategoryTool::class,
        CreateCategoryTool::class,
        UpdateCategoryTool::class,
        DeleteCategoryTool::class,
        ListMenusTool::class,
        GetMenuTool::class,
        CreateMenuTool::class,
        UpdateMenuTool::class,
        DeleteMenuTool::class,
        ListFormsTool::class,
        GetFormTool::class,
        CreateFormTool::class,
        UpdateFormTool::class,
        ListFormSubmissionsTool::class,
    ];
}

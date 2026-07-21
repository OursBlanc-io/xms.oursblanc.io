# XMS

An AI-drivable, block-based CMS package for Laravel, administered through Filament v5.

Pages are composed of self-describing blocks (Filament fields + a JSON Schema derived from
them automatically). An MCP server exposes full CRUD over pages so an AI agent — Claude Code,
Claude chat, or any other MCP client — can create, edit, translate, and publish pages without
any external documentation: it just calls `list_block_types` first.

## Requirements

- PHP 8.3+
- Laravel 12+ (13 supported)
- Filament v5
- MySQL 8

## Installation

### Local development (path repository)

In the host application's `composer.json`:

```json
"repositories": [
    { "type": "path", "url": "../xms.oursblanc.io", "options": { "symlink": true } }
],
"require": {
    "oursblanc-io/xms": "@dev"
}
```

```bash
composer require oursblanc-io/xms
php artisan migrate
php artisan storage:link
```

Register the package's Filament resources in your panel provider:

```php
use OursBlanc\Xms\Filament\XmsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(XmsPlugin::make());
}
```

Your `User` model must implement `Filament\Models\Contracts\FilamentUser` (Filament denies
panel access outside `APP_ENV=local` otherwise):

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // or your own authorization logic
    }
}
```

### Production (VCS / tagged release)

Point Composer at the package's GitHub repository and require a semver tag:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/OursBlanc-io/xms.oursblanc.io.git" }
],
"require": {
    "oursblanc-io/xms": "^0.1"
}
```

## Configuration

Publish the config file to tweak it:

```bash
php artisan vendor:publish --tag=xms-config
```

Key options in `config/xms.php` (all overridable via `.env`):

| Key | Env | Default | Purpose |
|---|---|---|---|
| `locales` | — | `['fr', 'en']` | Locales the CMS accepts. |
| `default_locale` | — | `fr` | The locale served without a prefix when `default_locale_hidden` is true. |
| `locale_in_url` | — | `true` | Whether locale is reflected in the URL at all. |
| `default_locale_hidden` | — | `true` | `/products/x` (default locale) vs `/en/products/x`. |
| `theme` | `XMS_THEME` | `null` | Active theme name (a `resources/themes/{name}` folder in the host app). |
| `generic_blocks_enabled` | — | `true` | Whether the 8 built-in blocks self-register. |
| `media_disk` | `XMS_MEDIA_DISK` | `public` | Disk for originals and conversions. |
| `revisions_per_page` | — | `50` | Revisions kept per page before older ones are pruned. |
| `cache.s_maxage` / `cache.max_age` | — | `3600` / `0` | `Cache-Control` on published page responses. |
| `cloudflare.zone_id` / `cloudflare.token` | `XMS_CLOUDFLARE_ZONE_ID` / `XMS_CLOUDFLARE_TOKEN` | `null` | Presence of both switches the cache invalidator from `NullInvalidator` to `CloudflareInvalidator`. |
| `mcp.route` | `XMS_MCP_ROUTE` | `/mcp/xms` | Where the MCP server is mounted. |

## The block system

A block is a plain PHP class: it declares its Filament form fields once, and XMS derives both
the JSON Schema (for the MCP tools) and the Laravel validation rules from them automatically.

```php
namespace App\Xms\Blocks;

use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;

class TestimonialBlock extends Block
{
    public static function name(): string
    {
        return 'testimonial'; // machine name, stored in the `blocks` json column
    }

    public static function label(): string
    {
        return 'Testimonial';
    }

    public static function description(): string
    {
        // Shown to AI agents via list_block_types — make it count.
        return 'A quote with an author name and an optional avatar.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('quote')->required(),
            TextInput::make('author')->required(),
            PageMediaUpload::make('avatar')->image(),
        ];
    }

    public static function mediaFields(): array
    {
        return ['avatar']; // marks it `integer` + `x-media: true` in the schema
    }

    public static function view(): string
    {
        return 'xms.testimonial'; // your own Blade view, in the host app or a theme
    }
}
```

Register it in a service provider:

```php
use OursBlanc\Xms\Blocks\BlockRegistry;

public function boot(): void
{
    $this->app->make(BlockRegistry::class)->register(\App\Xms\Blocks\TestimonialBlock::class);
}
```

Its view receives `$data` (the block's payload) and `$page`. For media fields, render
`<x-xms::picture :media-id="$data['avatar']" />` or `<x-xms::video :media-id="..." :poster-id="..." :url="..." />`.

Repeater-based media fields (a gallery of images inside a `Repeater`) are declared with
dot-star notation, e.g. `mediaFields(): array { return ['images.*.image']; }`.

## Themes

A theme is a folder in the **host app**, not the package: `resources/themes/{name}/`.

```
resources/themes/oursblanc/
├── theme.json          # { "assets": ["resources/themes/oursblanc/css/theme.css", ...] }
├── css/tokens.css       # design tokens (CSS custom properties)
├── css/theme.css        # imports tokens.css; overrides generic blocks via their stable classes
├── js/theme.js
└── views/               # ONLY custom-block views and layout overrides — never generic blocks
```

Generic blocks (`hero`, `text`, `image`, ...) always render the package's own Blade view. A
theme restyles them purely through CSS/JS, targeting the stable classes every generic view
exposes (`xms-block`, `xms-block--hero`, `xms-block__title`, ...) — never override their Blade.
A theme's `views/` directory is only for **custom blocks** it brings (with their own view) and
for layout overrides (`views/layouts/{template}.blade.php`, matched against a page's `template`
field, falling back to `views/layouts/default.blade.php`, falling back to the package's own).

1. Set `XMS_THEME=oursblanc` in `.env`.
2. Add the theme's CSS/JS entries to the host app's `vite.config.js` `input` array.
3. Run `npm run build` (or `npm run dev`).

## Cache / Cloudflare

Published pages are served with `Cache-Control: public, s-maxage=..., max-age=...`
(`SetCacheHeaders` middleware); anything else (drafts, 404s, authenticated requests, the
signed preview route) gets `no-store`. Saving, publishing, or unpublishing a page dispatches
a queued `PurgeCdnCacheJob` with that page's URL, its published sibling locales' URLs (their
`hreflang` tags reference this page), and the sitemap. Listen for the `xms.purge_urls` event
to append your own (a listing page, the homepage, ...):

```php
Event::listen('xms.purge_urls', fn (Page $page) => [url('/produits')]);
```

In production, set `XMS_CLOUDFLARE_ZONE_ID` and `XMS_CLOUDFLARE_TOKEN` (an API token scoped to
`Zone.Cache Purge` for that zone) and the invalidator switches from `NullInvalidator` (the
local default — no-op) to `CloudflareInvalidator` automatically. After a purge, Cloudflare can
take a few seconds to actually evict edge caches; a follow-up request may still hit a stale
cached response briefly — this is expected Cloudflare behavior, not a bug in the invalidator.

## Media

Every page implements `HasMedia`; each block's media fields attach to a collection named
`block-{uuid}`, so media stays scoped to the block that owns it even across duplication
between locales. Image conversions (`w480`, `w960`, `w1920`, WebP, quality 82) are generated
synchronously on upload. Media whose block has been removed from the page gets a 24h grace
period (`pending_deletion_at`) before the scheduled `xms:prune-media` command deletes it —
long enough to survive draft back-and-forth without leaking storage indefinitely.

Video posters are generated automatically via `ffmpeg` when available (detected at runtime,
skipped with a log entry otherwise) unless a poster is uploaded explicitly.

Set `xms.media_disk` (env `XMS_MEDIA_DISK`) to the disk used for original files and
conversions. Local development uses the `public` disk (run `php artisan storage:link` once).

### Scaleway Object Storage (production)

Configure the `s3` disk against Scaleway's S3-compatible endpoint and set `XMS_MEDIA_DISK=s3`.
CORS must be configured on the bucket for uploads from the admin panel to work:

```bash
aws s3api put-bucket-cors \
    --endpoint-url https://s3.fr-par.scw.cloud \
    --profile scaleway \
    --bucket <bucket-name> \
    --cors-configuration file://cors.json
```

The `mc` (MinIO) client fails with an `EOF` error against Scaleway's dotted bucket endpoints —
use the AWS CLI (`aws s3api`) instead, as above.

## MCP server (AI pilotage)

The MCP server is mounted at `xms.mcp.route` (default `/mcp/xms`) and requires a Bearer token
issued from the admin panel (Filament page **API tokens**): pick abilities among
`pages:read`, `pages:write`, `pages:publish`. The plaintext token is shown once, at creation —
store it somewhere safe.

### Connecting a client

Point any MCP-compatible client (Claude Code, Claude chat's custom connector, ...) at:

- Local: `http://oursblanc.test/mcp/xms`
- Production: `https://v2.oursblanc.io/mcp/xms`

with header `Authorization: Bearer <token>`.

### Tools

| Tool | Ability | Purpose |
|---|---|---|
| `list_block_types` | `pages:read` | Every block type's JSON Schema and media fields — call this first. |
| `list_pages` | `pages:read` | Filter by locale/status/search. |
| `get_page` | `pages:read` | Full page: blocks (with uuids), SEO, public/preview URLs, sibling locales. |
| `create_page` | `pages:write` | Always created as `draft`. |
| `update_page` | `pages:write` | Replaces only the fields you send; block uuids are preserved. |
| `patch_blocks` | `pages:write` | Targeted `insert`/`update`/`remove`/`move` operations on blocks. |
| `attach_media_from_url` | `pages:write` | Downloads a URL (SSRF-guarded) and attaches it to a block's direct media field. |
| `translate_page` | `pages:write` | Creates a sibling page; rejects blocks whose types/order don't match the source. |
| `publish_page` / `unpublish_page` | `pages:publish` | Flips status; triggers a CDN purge. |

Every write is recorded as a page revision attributed to the acting token
(`author_type: api_token`). Validation errors are structured JSON (field-level messages plus
the JSON Schema of the block type involved) so the calling AI can self-correct without needing
the docs above.

### Production token

Generate a dedicated production API token from the admin panel once `v2.oursblanc.io` is live,
scoped to only the abilities the connector actually needs.

## Testing

```bash
composer test
```

## License

MIT

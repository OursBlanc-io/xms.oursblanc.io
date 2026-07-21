# Changelog

All notable changes to this project are documented in this file.

## [0.1.0] — first tagged release

Initial feature-complete release of XMS.

### Added

- **Block system**: `Block` abstract class, `BlockRegistry`, `SchemaGenerator` (derives JSON
  Schema and Laravel validation rules from a block's Filament fields), `BlockValidator`. Eight
  generic blocks: `heading`, `text`, `hero`, `image`, `gallery`, `video`, `cta`, `columns`.
- **Data model**: `xms_translation_groups`, `xms_pages` (multilingual, flat with slashed slugs,
  draft/published), `xms_page_revisions` (auto-recorded before every content-changing update,
  configurable retention), `xms_api_tokens` (sha256-hashed, per-ability ability list).
- **Admin (Filament v5)**: `PageResource` with a `Builder` driven by the block registry,
  SEO/Settings tabs, publish/unpublish/duplicate-to-locale/history/preview actions, revision
  restore, an `ApiTokenResource` for issuing MCP tokens.
- **Rendering**: locale-aware catch-all route (hidden or prefixed default locale), theme system
  (host-app `resources/themes/{name}`, CSS/JS-only overrides of generic blocks, full Blade
  control for custom blocks), `<x-xms::seo-head>` (title, description, canonical, hreflang, OG,
  JSON-LD), `/sitemap.xml`, signed draft preview URLs.
- **Media**: `spatie/laravel-medialibrary` integration, `block-{uuid}` collections, synchronous
  WebP conversions (480/960/1920, quality 82), `<x-xms::picture>` / `<x-xms::video>`,
  `ffmpeg`-based video poster generation with graceful skip, 24h-grace-period deferred cleanup
  of orphaned media (`xms:prune-media`, scheduled daily).
- **Cache**: `CacheInvalidator` interface, `CloudflareInvalidator` / `NullInvalidator`,
  `SetCacheHeaders` middleware, `Page::urlsToPurge()` with an `xms.purge_urls` host-app hook,
  queued `PurgeCdnCacheJob` dispatched on save/publish/unpublish.
- **MCP server**: bearer-token auth against `xms_api_tokens` with per-tool ability checks, ten
  tools (`list_block_types`, `list_pages`, `get_page`, `create_page`, `update_page`,
  `patch_blocks`, `attach_media_from_url` with SSRF protections, `translate_page` with
  block-structure enforcement, `publish_page`, `unpublish_page`), structured/actionable
  validation errors, every write attributed to the acting token as a revision author.

### Notes

- Filament v5 denies panel access outside `APP_ENV=local` unless the host `User` model
  implements `FilamentUser` — see the README's installation section.
- Filament's `Builder` field doesn't preserve custom item identifiers across dehydration;
  `BuilderStateTransformer` carries each block's uuid as a hidden field inside its own data to
  work around this.

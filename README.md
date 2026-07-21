# XMS

An AI-drivable, block-based CMS package for Laravel, administered through Filament v5.

Status: under active development (Phase 0 — scaffolding). See `xms_plan_dev.md` for the full development plan.

## Requirements

- PHP 8.3+
- Laravel 12+
- Filament v5
- MySQL 8

## Installation (local development)

In the host application's `composer.json`:

```json
"repositories": [
    { "type": "path", "url": "../xms.oursblanc.io", "options": { "symlink": true } }
],
"require": {
    "oursblanc-io/xms": "*"
}
```

Then:

```bash
composer require oursblanc-io/xms
```

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

## Testing

```bash
composer test
```

## License

MIT

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

## Testing

```bash
composer test
```

## License

MIT

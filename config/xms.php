<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    */
    'locales' => ['fr', 'en'],

    'default_locale' => 'fr',

    'locale_in_url' => true,

    'default_locale_hidden' => true,

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    */
    'theme' => env('XMS_THEME'),

    /*
    |--------------------------------------------------------------------------
    | Blocks
    |--------------------------------------------------------------------------
    */
    'generic_blocks_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */
    'media_disk' => env('XMS_MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    |
    | Site-wide defaults used by <x-xms::seo-head> for Open Graph, Twitter
    | Card, and Organization/WebSite JSON-LD, on top of the per-page `seo`
    | fields. `organization_same_as` lists social profile URLs for the
    | Organization schema's `sameAs`.
    |
    */
    'seo' => [
        'site_name' => env('XMS_SEO_SITE_NAME'),
        'default_og_image' => env('XMS_SEO_DEFAULT_OG_IMAGE'),
        'twitter_handle' => env('XMS_SEO_TWITTER_HANDLE'),
        'organization_name' => env('XMS_SEO_ORGANIZATION_NAME'),
        'organization_logo' => env('XMS_SEO_ORGANIZATION_LOGO'),
        'organization_same_as' => array_filter(explode(',', (string) env('XMS_SEO_ORGANIZATION_SAME_AS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Revisions
    |--------------------------------------------------------------------------
    */
    'revisions_per_page' => 50,

    /*
    |--------------------------------------------------------------------------
    | Cache / Cloudflare
    |--------------------------------------------------------------------------
    */
    'cache' => [
        's_maxage' => 3600,
        'max_age' => 0,
    ],

    'cloudflare' => [
        'zone_id' => env('XMS_CLOUDFLARE_ZONE_ID'),
        'token' => env('XMS_CLOUDFLARE_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP
    |--------------------------------------------------------------------------
    */
    'mcp' => [
        'route' => env('XMS_MCP_ROUTE', '/mcp/xms'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Forms
    |--------------------------------------------------------------------------
    */
    'forms' => [
        // Name of the hidden honeypot input; bots that fill it in are silently rejected.
        'honeypot_field' => '_xms_hp',

        // Max submissions per minute per IP, across all forms.
        'throttle' => '10,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pexels
    |--------------------------------------------------------------------------
    |
    | Powers the "Search Pexels" picker on image/video fields in the admin.
    | Leave XMS_PEXELS_API_KEY unset to hide the picker entirely.
    |
    */
    'pexels' => [
        'api_key' => env('XMS_PEXELS_API_KEY', env('PEXELS_API_KEY')),
        'photo_max_bytes' => 10 * 1024 * 1024,
        'video_max_bytes' => 40 * 1024 * 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Manager
    |--------------------------------------------------------------------------
    |
    | A general-purpose file browser in the admin, scoped to a single fixed
    | root directory on `disk` — uploads, folders, renames, and deletes never
    | reach anywhere else on that disk.
    |
    */
    'media_manager' => [
        'disk' => env('XMS_MEDIA_MANAGER_DISK', env('XMS_MEDIA_DISK', 'public')),
        'root' => 'mediacontents',
    ],
];

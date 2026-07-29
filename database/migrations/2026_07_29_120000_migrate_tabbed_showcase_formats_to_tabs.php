<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tabbed Showcase dropped its fixed per-format device-demo animation (the
 * `demo` key + a hardcoded CSS/JS canned preview) in favour of letting each
 * tab hold any other block(s) as real content. This carries over every
 * existing tab's `title`/`description` into the new `tabs` shape (dropping
 * `demo` and the now-unused `live_label`), with empty `content` — there's no
 * automatic way to turn a canned animation into real block content, so
 * editors fill that in by hand afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('xms_pages')->get(['id', 'blocks'])->each(function ($row) {
            $blocks = collect(json_decode((string) $row->blocks, true))
                ->map(function (array $block) {
                    if (($block['type'] ?? null) !== 'tabbed-showcase') {
                        return $block;
                    }

                    $block['data']['tabs'] = collect($block['data']['formats'] ?? [])
                        ->map(fn (array $format) => [
                            'title' => $format['title'] ?? '',
                            'description' => $format['description'] ?? '',
                            'content' => [],
                        ])
                        ->all();

                    unset($block['data']['formats'], $block['data']['live_label']);

                    return $block;
                })
                ->all();

            DB::table('xms_pages')->where('id', $row->id)->update(['blocks' => json_encode($blocks)]);
        });
    }

    public function down(): void
    {
        // Data-only, one-way migration (the fixed demo animation isn't
        // reconstructible from `content`).
    }
};

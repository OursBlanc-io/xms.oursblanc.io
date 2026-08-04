<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `tabbed-showcase`'s per-tab `content` field is a nested Builder (an array
 * of other blocks — see TabbedShowcaseBlock::nestedBlockFields()), but
 * SchemaGenerator didn't know about the `Builder` component type and
 * reported/validated it as a plain string. Any content written through the
 * MCP tools while that was the case may have persisted a literal string
 * there instead of an array. Reset those to an empty array (same as new
 * tabs default to) — there's no way to safely infer real block content back
 * out of a stray string.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('xms_pages')->get(['id', 'blocks'])->each(function ($row) {
            $blocks = json_decode((string) $row->blocks, true);
            $changed = false;

            $blocks = collect($blocks)
                ->map(function (array $block) use (&$changed) {
                    if (($block['type'] ?? null) !== 'tabbed-showcase') {
                        return $block;
                    }

                    $block['data']['tabs'] = collect($block['data']['tabs'] ?? [])
                        ->map(function (array $tab) use (&$changed) {
                            if (isset($tab['content']) && ! is_array($tab['content'])) {
                                $tab['content'] = [];
                                $changed = true;
                            }

                            return $tab;
                        })
                        ->all();

                    return $block;
                })
                ->all();

            if ($changed) {
                DB::table('xms_pages')->where('id', $row->id)->update(['blocks' => json_encode($blocks)]);
            }
        });
    }

    public function down(): void
    {
        // Data-only, one-way migration (a discarded string value isn't
        // reconstructible).
    }
};

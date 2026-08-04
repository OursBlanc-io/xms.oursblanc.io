<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Nested blocks written into `tabbed-showcase` tabs' `content` before
 * SchemaGenerator/BlockValidator knew about Builder fields (see
 * coerce_tabbed_showcase_content_to_array) never got the `uuid` key that
 * PageRenderer::resolveBlock() requires — it went straight from the MCP
 * tools into storage with only `type`/`data`. Backfill one so existing pages
 * render instead of 500ing.
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
                            $tab['content'] = collect($tab['content'] ?? [])
                                ->map(function ($nested) use (&$changed) {
                                    if (is_array($nested) && ! isset($nested['uuid'])) {
                                        $nested['uuid'] = (string) Str::uuid();
                                        $changed = true;
                                    }

                                    return $nested;
                                })
                                ->all();

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
        // Data-only, additive migration (adding a missing key isn't worth
        // reversing).
    }
};

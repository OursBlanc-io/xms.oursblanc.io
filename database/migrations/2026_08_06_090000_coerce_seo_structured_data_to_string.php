<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `seo.structured_data` is a raw-JSON-LD string everywhere it's read (the
 * front-end <script> tag, the admin Textarea) — but MCP tools accept `seo`
 * as a loose object with no validation on its sub-fields, so `structured_data`
 * had been written as a real JSON object instead, crashing the front-end
 * render with "Array to string conversion" (every page in production had
 * this). Encode any array value back to its JSON string form.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('xms_pages')->get(['id', 'seo'])->each(function ($row) {
            $seo = json_decode((string) $row->seo, true);

            if (! is_array($seo) || ! isset($seo['structured_data']) || ! is_array($seo['structured_data'])) {
                return;
            }

            $seo['structured_data'] = json_encode($seo['structured_data']);

            DB::table('xms_pages')->where('id', $row->id)->update(['seo' => json_encode($seo)]);
        });
    }

    public function down(): void
    {
        // Data-only, one-way migration (re-inflating the string back into an
        // array isn't meaningfully "reversing" anything).
    }
};

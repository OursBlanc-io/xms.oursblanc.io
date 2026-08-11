<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A null `locale` means the page is locale-agnostic — served directly at
 * the site root (e.g. `/ma-page`) instead of under a `/fr/`- or
 * `/en/`-prefixed path, and excluded from hreflang/translation-sibling
 * handling. Same convention as `xms_code_snippets.locale` already uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            $table->string('locale', 10)->nullable(false)->change();
        });
    }
};

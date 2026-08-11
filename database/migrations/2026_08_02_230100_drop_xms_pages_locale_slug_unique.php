<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Same issue as the translation_group_id/locale constraint dropped in the
 * previous migration: MySQL has no partial/filtered unique index, so a
 * trashed page kept occupying its (locale, slug) slot at the database
 * engine level — blocking a new page (or "Duplicate to locale", which
 * reuses the source page's slug) from reusing it. Enforced at the
 * application level instead (see PageForm, CreatePageTool, UpdatePageTool,
 * TranslatePageTool — all updated to exclude trashed rows explicitly).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            $table->dropUnique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            $table->unique(['locale', 'slug']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL has no partial/filtered unique index, so `unique(['translation_group_id',
 * 'locale'])` didn't know a row was soft-deleted — a trashed page kept
 * occupying its (group, locale) slot at the database engine level, so
 * "Duplicate to locale" (EditPage) would offer that locale again (its query
 * correctly excludes trashed rows via the SoftDeletes global scope), then
 * fail on insert with a raw integrity-constraint violation.
 *
 * Enforced at the application level instead (see EditPage::duplicateAction()
 * and TranslatePageTool, both of which already query non-trashed rows only),
 * which is trash-aware without needing a partial index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            // The composite unique index being dropped is also what MySQL
            // uses to satisfy translation_group_id's own foreign key — it
            // refuses to drop it otherwise, so a plain index takes over
            // that role first.
            $table->index('translation_group_id');
            $table->dropUnique(['translation_group_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            $table->dropIndex(['translation_group_id']);
            $table->unique(['translation_group_id', 'locale']);
        });
    }
};

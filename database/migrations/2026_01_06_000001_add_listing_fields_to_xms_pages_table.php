<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            // Distinct from `title`/`seo`: how this page appears as a card in
            // a page_list block, e.g. a shorter/punchier title than the
            // on-page one. Falls back to `title` when empty.
            $table->string('list_title')->nullable()->after('title');
            $table->text('list_excerpt')->nullable()->after('list_title');

            // Freeform key/value metadata (e.g. format: "video", visual_type:
            // "portrait"), on top of Category — lets page_list facet-filter
            // without predefining a fixed taxonomy per site.
            $table->json('meta')->nullable()->after('seo');
        });
    }

    public function down(): void
    {
        Schema::table('xms_pages', function (Blueprint $table) {
            $table->dropColumn(['list_title', 'list_excerpt', 'meta']);
        });
    }
};

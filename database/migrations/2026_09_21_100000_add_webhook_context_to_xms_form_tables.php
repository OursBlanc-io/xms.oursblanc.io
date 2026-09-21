<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Context captured at submission time, so a webhook consumer can tie a
     * submission to a known web visitor and to the page it came from.
     *
     * These live on the submission rather than only travelling in the webhook
     * payload for two reasons: the delivery job runs on a queue and no longer
     * has the HTTP request, and a webhook that can be replayed has to be able
     * to rebuild the exact same payload later.
     */
    public function up(): void
    {
        Schema::table('xms_forms', function (Blueprint $table) {
            $table->string('webhook_secret')->nullable()->after('webhook_enabled');
        });

        Schema::table('xms_form_submissions', function (Blueprint $table) {
            $table->string('visitor_id', 64)->nullable()->after('ip_address');
            $table->text('page_url')->nullable()->after('visitor_id');
            $table->string('user_agent', 512)->nullable()->after('page_url');
        });
    }

    public function down(): void
    {
        Schema::table('xms_forms', function (Blueprint $table) {
            $table->dropColumn('webhook_secret');
        });

        Schema::table('xms_form_submissions', function (Blueprint $table) {
            $table->dropColumn(['visitor_id', 'page_url', 'user_agent']);
        });
    }
};

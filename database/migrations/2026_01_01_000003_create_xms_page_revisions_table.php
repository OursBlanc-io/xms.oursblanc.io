<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xms_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('xms_pages')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug', 500);
            $table->json('blocks');
            $table->json('seo');
            $table->enum('author_type', ['user', 'api_token']);
            $table->string('author_id');
            $table->timestamp('created_at')->nullable();

            $table->index(['page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xms_page_revisions');
    }
};

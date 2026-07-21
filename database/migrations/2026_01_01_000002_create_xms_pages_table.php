<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xms_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_group_id')->nullable()
                ->constrained('xms_translation_groups')->nullOnDelete();
            $table->string('locale', 10);
            $table->string('slug', 500);
            $table->string('title');
            $table->json('blocks');
            $table->json('seo');
            $table->string('template')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['locale', 'slug']);
            $table->unique(['translation_group_id', 'locale']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xms_pages');
    }
};

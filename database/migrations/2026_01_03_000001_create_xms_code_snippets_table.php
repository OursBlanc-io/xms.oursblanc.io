<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xms_code_snippets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('placement');
            $table->string('locale', 10)->nullable();
            $table->longText('code');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['placement', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xms_code_snippets');
    }
};

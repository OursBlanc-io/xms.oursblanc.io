<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xms_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('xms_forms')->cascadeOnDelete();
            $table->string('label');
            $table->string('key');
            $table->enum('type', ['text', 'email', 'textarea', 'select', 'checkbox']);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xms_form_fields');
    }
};

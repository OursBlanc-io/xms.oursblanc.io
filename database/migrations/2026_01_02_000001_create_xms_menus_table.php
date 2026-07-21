<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xms_menus', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->string('locale', 10);
            $table->string('name');
            $table->json('items');
            $table->timestamps();

            $table->unique(['location', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xms_menus');
    }
};

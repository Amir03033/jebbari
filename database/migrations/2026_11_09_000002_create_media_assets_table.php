<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per uploaded image. The file and its conversions live in Spatie's
        // `media` table. Content references assets by id, so media choices can be
        // drafted like any other field.
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('alt')->nullable();            // {"nl": "...", "en": "..."}
            $table->boolean('is_decorative')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};

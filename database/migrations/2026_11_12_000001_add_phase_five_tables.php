<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Case studies: milestones, release notes, a live demo and a before/after pair.
        Schema::table('projects', function (Blueprint $table) {
            $table->json('timeline')->nullable()->after('outcome');   // [{date, title:{nl,en}, text:{nl,en}}]
            $table->json('changelog')->nullable()->after('timeline'); // [{version, date, notes:{nl,en}}]
            $table->string('demo_url')->nullable()->after('url');
            $table->foreignId('before_media_id')->nullable()->after('cover_media_id')->constrained('media_assets')->nullOnDelete();
            $table->foreignId('after_media_id')->nullable()->after('before_media_id')->constrained('media_assets')->nullOnDelete();
        });

        // Cookieless statistics: one counter per day and path. No visitor data at all.
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('path', 190);
            $table->unsignedInteger('views')->default(0);
            $table->unique(['date', 'path']);
        });

        // Newsletter sign-ups, confirmed by email (double opt-in).
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('locale', 2)->default('nl');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('page_views');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('before_media_id');
            $table->dropConstrainedForeignId('after_media_id');
            $table->dropColumn(['timeline', 'changelog', 'demo_url']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_watches', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('url', 2048);
            $table->string('domain')->index();
            $table->string('locale', 5)->default('nl');
            $table->json('last_scores')->nullable();
            $table->foreignId('last_scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('runs')->default(0);
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->unique(['email', 'domain']);
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->foreignId('scan_watch_id')->nullable()->after('contact_consent')->constrained('scan_watches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scan_watch_id');
        });
        Schema::dropIfExists('scan_watches');
    }
};

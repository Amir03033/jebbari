<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();
            $table->string('url', 2048);
            $table->string('domain')->index();
            $table->string('email');
            $table->string('locale', 5);
            $table->string('status', 16)->default('pending')->index();
            $table->json('scores')->nullable();
            $table->json('results')->nullable();
            $table->string('error', 32)->nullable();
            $table->string('ip_hash', 64)->index();
            $table->boolean('contact_consent')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('budget')->nullable();
            $table->text('message');
            $table->string('locale', 2)->default('nl');
            $table->string('status', 20)->default('new');
            $table->timestamp('read_at')->nullable();      // null means unread
            $table->timestamp('completed_at')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['status', 'read_at']);
        });

        Schema::create('inquiry_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('location', 10);               // header | footer
            $table->string('page_key')->nullable();
            $table->string('url')->nullable();
            $table->json('label');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['navigation_items', 'settings', 'inquiry_notes', 'inquiries'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

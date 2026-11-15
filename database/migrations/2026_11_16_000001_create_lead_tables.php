<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 40)->nullable();
            $table->string('company')->nullable();
            $table->string('source', 16)->index();
            $table->string('status', 16)->default('new')->index();
            $table->json('choices')->nullable();
            $table->unsignedInteger('estimate_min')->nullable();
            $table->unsignedInteger('estimate_max')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('contact_consent')->default(false);
            $table->unsignedTinyInteger('wizard_step')->default(1);
            $table->string('locale', 5)->default('nl');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('price_items', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('group', 16)->index();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('price_min')->nullable();
            $table->unsignedInteger('price_max')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('location')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday'); // ISO: 1 = Monday ... 7 = Sunday
            $table->time('starts_at');
            $table->time('ends_at');
            $table->foreignId('appointment_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained()->restrictOnDelete();
            $table->dateTime('starts_at')->index(); // UTC
            $table->dateTime('ends_at');            // UTC
            $table->string('status', 16)->default('confirmed')->index();
            // The start time while confirmed, null once cancelled: a unique index stops two bookings at the same moment.
            $table->string('slot_key', 32)->nullable()->unique();
            $table->text('message')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('blocked_dates');
        Schema::dropIfExists('availability_rules');
        Schema::dropIfExists('appointment_types');
        Schema::dropIfExists('price_items');
        Schema::dropIfExists('leads');
    }
};

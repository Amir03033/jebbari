<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('previews', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('repo_url');
            $table->string('branch')->default('main');
            $table->string('locale', 2)->default('nl');
            $table->boolean('dev_dependencies')->default(false);
            $table->string('status')->default('pending')->index();
            $table->longText('log')->nullable();
            // The secret in the client's link, and the cookie value the subdomain checks.
            $table->string('token', 64)->unique();
            $table->string('access_key', 64);
            $table->timestamp('deployed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('change_request')->nullable();
            $table->timestamp('change_requested_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previews');
    }
};

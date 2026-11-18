<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('monitor')->default(false)->after('url');
        });

        Schema::create('uptime_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->boolean('ok');
            $table->unsignedSmallInteger('status')->nullable();
            $table->unsignedInteger('ms')->nullable();
            $table->timestamp('checked_at')->index();
            $table->index(['project_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_checks');
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('monitor');
        });
    }
};

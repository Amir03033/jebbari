<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Short description shown on project cards (the long text stays in brief/built).
        Schema::table('projects', function (Blueprint $table) {
            $table->json('summary')->nullable()->after('kind');
        });

        // What the client gets from the service, shown next to the deliverables.
        Schema::table('services', function (Blueprint $table) {
            $table->json('benefits')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('summary'));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('benefits'));
    }
};

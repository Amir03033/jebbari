<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Case-study sections: what the project set out to do, and what it does.
        // Both are one point per line, in Dutch and English.
        Schema::table('projects', function (Blueprint $table) {
            $table->json('objectives')->nullable()->after('brief');
            $table->json('features')->nullable()->after('built');
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['objectives', 'features']));
    }
};

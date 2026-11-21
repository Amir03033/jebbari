<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('previews', function (Blueprint $table) {
            // Keep the preview database (content, accounts, uploads) on a redeploy of the same preview.
            $table->boolean('keep_database')->default(true)->after('dev_dependencies');
            // Redeploy by itself when the branch gets a new commit.
            $table->boolean('auto_update')->default(true)->after('keep_database');
            // The commit of the last build attempt (manual or automatic).
            $table->string('commit_sha', 40)->nullable()->after('auto_update');
            // The preview database currently holds this preview's data.
            $table->boolean('owns_database')->default(false)->after('commit_sha');
        });

        // The preview that is live now was built with an empty, seeded database
        // that still belongs to it: keep its content on the next deploy.
        DB::table('previews')->where('status', 'ready')->update(['owns_database' => true]);
    }

    public function down(): void
    {
        Schema::table('previews', function (Blueprint $table) {
            $table->dropColumn(['keep_database', 'auto_update', 'commit_sha', 'owns_database']);
        });
    }
};

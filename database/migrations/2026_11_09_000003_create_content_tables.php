<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Columns shared by every publishable content type. */
    private function publishing(Blueprint $table): void
    {
        $table->string('status', 12)->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->json('draft_data')->nullable();   // unpublished changes; live columns stay untouched
        $table->json('seo')->nullable();
    }

    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();           // home, about, services, work, contact, privacy, terms
            $table->string('kind', 10)->default('system'); // system | legal
            $table->json('title');
            $table->string('slug_nl')->nullable()->unique(); // legal pages only; system slugs live in code
            $table->string('slug_en')->nullable()->unique();
            $table->json('blocks')->nullable();
            $this->publishing($table);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug_nl')->nullable()->unique();
            $table->string('slug_en')->nullable()->unique();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->json('included')->nullable();
            $table->json('not_included')->nullable();
            $table->json('timeline')->nullable();
            $table->json('tags')->nullable();
            $table->json('packages')->nullable();      // drafted together with the service
            $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $this->publishing($table);
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug_nl')->nullable()->unique();
            $table->string('slug_en')->nullable()->unique();
            $table->json('title');
            $table->json('kind')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('client_name')->nullable();
            $table->json('brief')->nullable();
            $table->json('built')->nullable();
            $table->json('outcome')->nullable();
            $table->json('stack')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('featured_sort')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('cover_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->json('gallery')->nullable();       // [{media_id, caption:{nl,en}}]
            $this->publishing($table);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('role')->nullable();
            $table->json('quote');
            $table->boolean('is_featured')->default(false);
            $table->boolean('permission_confirmed')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 12)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('draft_data')->nullable();
            $table->timestamps();
        });

        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug_nl')->nullable()->unique();
            $table->string('slug_en')->nullable()->unique();
            $table->json('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug_nl')->nullable()->unique();
            $table->string('slug_en')->nullable()->unique();
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('body')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $this->publishing($table);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['posts', 'post_categories', 'testimonials', 'projects', 'services', 'pages'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

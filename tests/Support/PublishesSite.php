<?php

namespace Tests\Support;

use App\Models\Page;
use Database\Seeders\CmsSeeder;

trait PublishesSite
{
    /** Seed the brief's content and publish the system pages (legal pages stay drafts). */
    protected function publishSite(array $keys = ['home', 'work', 'services', 'about', 'blog', 'contact']): void
    {
        $this->seed(CmsSeeder::class);

        foreach ($keys as $key) {
            Page::where('key', $key)->firstOrFail()->publish();
        }
    }
}

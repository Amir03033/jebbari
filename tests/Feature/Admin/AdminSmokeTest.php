<?php

namespace Tests\Feature\Admin;

use App\Models\Inquiry;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Database\Seeders\CmsSeeder;
use Tests\Support\AdminTestCase;

class AdminSmokeTest extends AdminTestCase
{
    public function test_every_admin_page_renders_for_the_owner(): void
    {
        $this->seed(CmsSeeder::class);
        $owner = $this->owner();
        $project = Project::create(['title' => ['nl' => 'Test', 'en' => 'Test']]);
        $post = Post::create(['title' => ['nl' => 'Artikel', 'en' => 'Article']]);
        $testimonial = Testimonial::create(['name' => 'A', 'quote' => ['nl' => 'x', 'en' => 'y']]);
        $inquiry = Inquiry::create(['name' => 'Jan', 'email' => 'jan@example.com', 'message' => 'Hallo']);

        $urls = [
            '/admin', '/admin/pages', '/admin/pages/create', '/admin/pages/'.Page::where('key', 'home')->value('id').'/edit',
            '/admin/services', '/admin/services/create', '/admin/services/'.Service::first()->id.'/edit',
            '/admin/projects', '/admin/projects/create', '/admin/projects/'.$project->id.'/edit',
            '/admin/testimonials', '/admin/testimonials/create', '/admin/testimonials/'.$testimonial->id.'/edit',
            '/admin/posts', '/admin/posts/create', '/admin/posts/'.$post->id.'/edit',
            '/admin/post-categories', '/admin/post-categories/create',
            '/admin/media-assets', '/admin/media-assets/create',
            '/admin/inquiries', '/admin/inquiries/'.$inquiry->id,
            '/admin/navigation-items', '/admin/navigation-items/create',
            '/admin/users', '/admin/users/create', '/admin/logboek',
            '/admin/company-settings', '/admin/contact-settings', '/admin/social-settings', '/admin/header-footer-settings', '/admin/seo-settings',
        ];

        foreach ($urls as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
    }
}

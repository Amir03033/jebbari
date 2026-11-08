<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use PublishesSite, RefreshDatabase;

    public function test_root_redirects_by_accept_language(): void
    {
        $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])->assertRedirect('/en');
        $this->get('/', ['Accept-Language' => 'nl-NL,nl;q=0.9,en;q=0.5'])->assertRedirect('/nl');
    }

    public function test_root_defaults_to_dutch_for_unsupported_or_missing_language(): void
    {
        // Symfony's test requests add an English Accept-Language unless it is overridden.
        $this->get('/', ['Accept-Language' => ''])->assertRedirect('/nl');
        $this->get('/', ['Accept-Language' => 'fr-FR,fr;q=0.9'])->assertRedirect('/nl');
    }

    public function test_pages_render_in_their_locale_with_translated_slugs(): void
    {
        $this->publishSite();

        $this->get('/nl/diensten')->assertOk()->assertSee('<html lang="nl"', false)->assertSee('Diensten');
        $this->get('/en/services')->assertOk()->assertSee('<html lang="en"', false)->assertSee('Services');
    }

    public function test_untranslated_slug_in_wrong_locale_is_not_found(): void
    {
        $this->get('/en/diensten')->assertNotFound();
        $this->get('/nl/services')->assertNotFound();
    }

    public function test_language_switch_points_to_the_same_page_in_the_other_locale(): void
    {
        $this->publishSite();

        $this->get('/nl/werk')->assertSee(url('/en/work'), false);
        $this->get('/en/about')->assertSee(url('/nl/over-ons'), false);
    }

    public function test_hreflang_and_canonical_are_present(): void
    {
        $this->publishSite();

        $this->get('/en/work')
            ->assertSee('<link rel="canonical" href="'.url('/en/work').'">', false)
            ->assertSee('hreflang="nl" href="'.url('/nl/werk').'"', false)
            ->assertSee('hreflang="x-default" href="'.url('/nl/werk').'"', false);
    }

    public function test_style_page_is_noindex_and_hidden_in_production(): void
    {
        $this->get('/_style')->assertOk()->assertSee('noindex', false);

        $this->app->detectEnvironment(fn () => 'production');
        $this->get('/_style')->assertNotFound();
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Livewire remembers in a static that an admin page rendered a component, and would then
        // inject its styles into the next public page of the same test run. Each test starts clean.
        SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;
        SupportAutoInjectedAssets::$forceAssetInjection = false;
    }

    /**
     * The page's HTML without the command menu. The menu lists every live page,
     * project and article, so "this page does not show X" must look outside it.
     */
    protected function body(string $url): string
    {
        $html = $this->get($url)->assertOk()->getContent();

        return preg_replace('#<dialog id="command-menu".*?</dialog>#s', '', $html);
    }
}

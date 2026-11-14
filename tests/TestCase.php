<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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

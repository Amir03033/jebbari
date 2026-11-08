<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    /**
     * Phase 1 only has the shell. Real page content arrives in later phases,
     * so every page renders the same neutral stub.
     */
    public function __invoke(string $page): View
    {
        return view('pages.stub', ['page' => $page]);
    }
}

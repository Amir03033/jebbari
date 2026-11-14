<?php

namespace App\Http\Controllers;

use App\Content\Site;
use Illuminate\Http\Response;

/** The blog as an RSS feed, per language. */
class FeedController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(Site::page('blog'), 404);

        $posts = Site::visible(Site::postsQuery()->limit(30)->get());

        return response(view('feed', ['posts' => $posts])->render(), 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}

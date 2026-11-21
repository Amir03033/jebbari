<?php

namespace Tests\Feature;

use App\Enums\PreviewStatus;
use App\Filament\Resources\Previews\Pages\CreatePreview;
use App\Filament\Resources\Previews\Pages\ListPreviews;
use App\Filament\Resources\Previews\Pages\ViewPreview;
use App\Jobs\ClearPreview;
use App\Jobs\DeployPreview;
use App\Mail\PreviewApproved;
use App\Mail\PreviewChangesRequested;
use App\Models\Preview;
use App\Support\PreviewServer;
use App\Support\PreviewUpdates;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Support\AdminTestCase;

class PreviewDeployTest extends AdminTestCase
{
    private string $dir;

    private string $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = config('database.default');
        $this->dir = sys_get_temp_dir().'/preview-test-'.uniqid();
        mkdir($this->dir.'/preview.test/httpdocs', 0777, true);

        config([
            'preview.path' => $this->dir.'/preview.test/httpdocs',
            'preview.url' => 'https://preview.jebbari.test',
            'preview.cookie_domain' => null,
            'preview.db' => ['host' => '127.0.0.1', 'port' => '3306', 'database' => 'preview_db', 'username' => 'preview_user', 'password' => 'p@ss$\'word"'],
            'preview.github_token' => 'github_pat_SECRET',
        ]);

        RateLimiter::clear('review');
    }

    protected function tearDown(): void
    {
        config(['database.default' => $this->connection]);
        exec('rm -rf '.escapeshellarg($this->dir));

        parent::tearDown();
    }

    /** A stand-in for scripts/preview-deploy.sh that prints what it received. */
    private function fakeScript(int $exit = 0): void
    {
        $path = $this->dir.'/fake.sh';
        file_put_contents($path, <<<SH
#!/usr/bin/env bash
echo "mode=\$1"
echo "repo=\$PREVIEW_REPO branch=\$PREVIEW_BRANCH"
echo "db=\${DB_DATABASE:-unset} appkey=\${APP_KEY:-unset}"
echo "secret=preview_user:p@ss\$'word\" token=github_pat_SECRET"
echo "git=\${GIT_CONFIG_KEY_0:-none}"
echo "fresh=\$PREVIEW_FRESH"
echo "Commit-SHA: 0123456789abcdef0123456789abcdef01234567"
printf '%s' "\$PREVIEW_ENV" > "{$this->dir}/env.out"
exit {$exit}
SH);
        config(['preview.script' => $path]);
    }

    private function preview(array $attributes = []): Preview
    {
        return Preview::create($attributes + [
            'client_name' => 'Bakkerij de Vries',
            'repo_url' => 'https://github.com/klant/website.git',
            'branch' => 'main',
            'status' => PreviewStatus::Ready,
            'deployed_at' => now()->subHour(),
        ]);
    }

    // ------------------------------------------------------------ validation

    public function test_only_https_github_repository_urls_are_accepted(): void
    {
        $this->assertSame('https://github.com/klant/website.git', Preview::normalizeRepoUrl('https://github.com/klant/website'));
        $this->assertSame('https://github.com/klant/website.git', Preview::normalizeRepoUrl('https://github.com/klant/website.git'));
        $this->assertSame('https://github.com/a-b/c.d_e.git', Preview::normalizeRepoUrl('https://github.com/a-b/c.d_e/'));
        $this->assertSame('https://github.com/klant/website.git', Preview::normalizeRepoUrl(" https://github.com/klant/website\n"));

        foreach ([
            'http://github.com/klant/website',
            'git@github.com:klant/website.git',
            'https://gitlab.com/klant/website',
            'https://github.com.evil.test/klant/website',
            'https://github.com/klant/website;rm -rf /',
            'https://github.com/klant/web site',
            'https://github.com/klant/$(id)',
            'https://github.com/klant/website/tree/main',
            'https://github.com/klant/..',
            'https://github.com/-klant/website',
            "https://github.com/klant/website\nfoo",
            'https://user:pass@github.com/klant/website',
            'https://github.com/klant/website?x=1',
            '--upload-pack=touch /tmp/x',
        ] as $bad) {
            $this->assertNull(Preview::normalizeRepoUrl($bad), $bad);
        }
    }

    public function test_branch_names_are_restricted(): void
    {
        foreach (['main', 'feature/new-home', 'release-1.2', 'v2_x'] as $ok) {
            $this->assertTrue(Preview::validBranch($ok), $ok);
        }

        foreach (['', '-main', '--upload-pack=x', 'a..b', 'a b', 'main;id', '$(id)', 'a//b', 'main/', '/main', 'x.lock', 'a`b`', str_repeat('a', 101)] as $bad) {
            $this->assertFalse(Preview::validBranch($bad), $bad);
        }
    }

    public function test_a_new_preview_gets_a_long_random_token_and_an_expiry(): void
    {
        $a = $this->preview();
        $b = $this->preview();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $a->token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $a->access_key);
        $this->assertNotSame($a->token, $b->token);
        $this->assertNotSame($a->token, $a->access_key);
        $this->assertTrue($a->expires_at->between(now()->addDays(13), now()->addDays(15)));
        $this->assertArrayNotHasKey('token', $a->toArray());
    }

    // ------------------------------------------------------------ admin

    public function test_the_admin_pages_render_for_the_owner_only(): void
    {
        $preview = $this->preview();
        $owner = $this->owner();

        foreach (['/admin/previews', '/admin/previews/create', '/admin/previews/'.$preview->id, '/admin/previews/'.$preview->id.'/edit'] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }

        $this->actingAs($this->editor())->get('/admin/previews')->assertForbidden();
    }

    public function test_creating_a_preview_queues_a_deploy_and_validates_the_url(): void
    {
        Queue::fake();
        $this->actingAs($this->owner());

        Livewire::test(CreatePreview::class)
            ->fillForm(['client_name' => 'Klant', 'repo_url' => 'https://github.com/klant/site; rm -rf ~', 'branch' => 'main'])
            ->call('create')
            ->assertHasFormErrors(['repo_url']);
        $this->assertSame(0, Preview::count());

        Livewire::test(CreatePreview::class)
            ->fillForm(['client_name' => 'Klant', 'repo_url' => 'https://github.com/klant/site', 'branch' => 'main', 'locale' => 'nl'])
            ->call('create')
            ->assertHasNoFormErrors();

        $preview = Preview::sole();
        $this->assertSame('https://github.com/klant/site.git', $preview->repo_url);
        $this->assertSame(PreviewStatus::Pending, $preview->status);
        Queue::assertPushedOn('previews', DeployPreview::class);
    }

    public function test_deploy_replaces_the_previous_preview_and_refuses_while_a_build_runs(): void
    {
        Queue::fake();
        $this->actingAs($this->owner());
        $old = $this->preview();
        $new = $this->preview(['status' => PreviewStatus::Failed]);
        $oldKey = $new->access_key;

        Livewire::test(ViewPreview::class, ['record' => $new->getKey()])->callAction('deploy');

        $this->assertSame(PreviewStatus::Cleared, $old->fresh()->status);
        $this->assertSame(PreviewStatus::Pending, $new->fresh()->status);
        // The key stays: a client who is looking keeps access after an update.
        $this->assertSame($oldKey, $new->fresh()->access_key);
        Queue::assertPushed(DeployPreview::class, 1);

        // A build is queued: a second deploy is refused.
        $other = $this->preview(['status' => PreviewStatus::Failed]);
        Livewire::test(ViewPreview::class, ['record' => $other->getKey()])->assertActionDisabled('deploy');
        Queue::assertPushed(DeployPreview::class, 1);
    }

    public function test_clearing_marks_every_preview_cleared_and_queues_the_clear_job(): void
    {
        Queue::fake();
        $this->actingAs($this->owner());
        $preview = $this->preview();

        Livewire::test(ListPreviews::class)->callAction('clear');

        $this->assertSame(PreviewStatus::Cleared, $preview->fresh()->status);
        Queue::assertPushedOn('previews', ClearPreview::class);
    }

    // ------------------------------------------------------------ the job

    public function test_the_job_runs_the_script_with_a_clean_environment_and_masks_secrets(): void
    {
        $this->fakeScript();
        $preview = $this->preview(['status' => PreviewStatus::Pending, 'deployed_at' => null, 'approved_at' => now()]);

        (new DeployPreview($preview))->handle(app(PreviewServer::class));

        $preview->refresh();
        $this->assertSame(PreviewStatus::Ready, $preview->status);
        $this->assertNotNull($preview->deployed_at);
        $this->assertNull($preview->approved_at);
        $this->assertStringContainsString('mode=deploy', $preview->log);
        $this->assertStringContainsString('repo=https://github.com/klant/website.git branch=main', $preview->log);
        // Jebbari's own APP_KEY and database never reach the script.
        $this->assertStringContainsString('db=unset appkey=unset', $preview->log);
        $this->assertStringContainsString('git=http.https://github.com/.extraheader', $preview->log);
        $this->assertStringNotContainsString('github_pat_SECRET', $preview->log);
        $this->assertStringNotContainsString("p@ss\$'word", $preview->log);
        $this->assertStringContainsString('Ready.', $preview->log);

        $env = file_get_contents($this->dir.'/env.out');
        $this->assertStringContainsString("DB_DATABASE='preview_db'", $env);
        $this->assertStringContainsString('DB_PASSWORD="p@ss\\$\'word\\""', $env);
        $this->assertStringContainsString("APP_URL='https://preview.jebbari.test'", $env);
    }

    public function test_a_failing_script_marks_the_preview_failed(): void
    {
        $this->fakeScript(3);
        $preview = $this->preview(['status' => PreviewStatus::Pending]);

        (new DeployPreview($preview))->handle(app(PreviewServer::class));

        $this->assertSame(PreviewStatus::Failed, $preview->fresh()->status);
        $this->assertStringContainsString('exit code 3', $preview->fresh()->log);
    }

    public function test_the_job_refuses_jebbaris_own_database_and_a_missing_path(): void
    {
        $this->fakeScript();
        config(['preview.path' => null]);
        $preview = $this->preview(['status' => PreviewStatus::Pending]);

        (new DeployPreview($preview))->handle(app(PreviewServer::class));

        $this->assertSame(PreviewStatus::Failed, $preview->fresh()->status);
        $this->assertStringContainsString('PREVIEW_PATH is not set', $preview->fresh()->log);
        $this->assertFileDoesNotExist($this->dir.'/env.out');

        config([
            'preview.path' => $this->dir.'/preview.test/httpdocs',
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'preview_db',
            'database.connections.mysql.host' => '127.0.0.1',
        ]);
        $this->expectExceptionMessage('Jebbari\'s own database');
        app(PreviewServer::class)->assertConfigured();
    }

    public function test_a_preview_cleared_during_its_build_stays_cleared(): void
    {
        // Already cleared while it waited in the queue: nothing runs.
        $this->fakeScript();
        $waiting = $this->preview(['status' => PreviewStatus::Cleared]);
        (new DeployPreview($waiting))->handle(app(PreviewServer::class));
        $this->assertNull($waiting->fresh()->log);
        $this->assertFileDoesNotExist($this->dir.'/env.out');

        // "Preview wissen" while the script runs: the result does not bring it back.
        $preview = $this->preview(['status' => PreviewStatus::Pending]);
        (new DeployPreview($preview))->handle(new class extends PreviewServer
        {
            public function run(string $mode, ?Preview $preview, callable $output, bool $fresh = true): int
            {
                Preview::query()->whereKey($preview->id)->update(['status' => PreviewStatus::Cleared]);

                return 0;
            }
        });

        $this->assertSame(PreviewStatus::Cleared, $preview->fresh()->status);
        $this->assertStringContainsString('cleared in the meantime', $preview->fresh()->log);
    }

    public function test_the_job_uses_its_own_queue_with_a_long_timeout(): void
    {
        $job = new DeployPreview($this->preview());

        $this->assertSame('previews', $job->connection);
        $this->assertSame('previews', $job->queue);
        $this->assertGreaterThan(config('preview.timeout'), $job->timeout);
        $this->assertGreaterThan($job->timeout, config('queue.connections.previews.retry_after'));
    }

    // ------------------------------------------------------------ keeping the database, automatic updates

    public function test_the_database_is_kept_only_for_a_redeploy_of_the_preview_that_owns_it(): void
    {
        $this->fakeScript();
        $a = $this->preview(['status' => PreviewStatus::Pending]);

        // First build: always empty and seeded. Afterwards it owns the database.
        (new DeployPreview($a))->handle(app(PreviewServer::class));
        $a->refresh();
        $this->assertStringContainsString('fresh=1', $a->log);
        $this->assertTrue($a->owns_database);
        $this->assertSame('0123456789abcdef0123456789abcdef01234567', $a->commit_sha);

        // Redeploy of the same preview: kept.
        Queue::fake();
        $a->deploy();
        (new DeployPreview($a->fresh()))->handle(app(PreviewServer::class));
        $this->assertStringContainsString('fresh=0', $a->fresh()->log);
        $this->assertStringContainsString('Database: kept', $a->fresh()->log);

        // Another preview takes the database: A starts over next time.
        $b = $this->preview(['status' => PreviewStatus::Failed]);
        $b->deploy();
        (new DeployPreview($b->fresh()))->handle(app(PreviewServer::class));
        $this->assertStringContainsString('fresh=1', $b->fresh()->log);
        $this->assertFalse($a->fresh()->owns_database);
        $this->assertTrue($b->fresh()->owns_database);

        // Switched off: emptied even when it owns the database.
        $b->update(['keep_database' => false]);
        $b->deploy();
        (new DeployPreview($b->fresh()))->handle(app(PreviewServer::class));
        $this->assertStringContainsString('fresh=1', $b->fresh()->log);

        // Clearing: nobody owns it any more.
        Preview::clearServer();
        $this->assertSame(0, Preview::where('owns_database', true)->count());
    }

    public function test_a_failed_fresh_build_leaves_the_database_without_owner(): void
    {
        $this->fakeScript(1);
        $a = $this->preview(['status' => PreviewStatus::Pending, 'keep_database' => false, 'owns_database' => true]);

        (new DeployPreview($a))->handle(app(PreviewServer::class));

        $this->assertFalse($a->fresh()->owns_database);
    }

    public function test_a_new_commit_on_the_branch_redeploys_the_live_preview(): void
    {
        Queue::fake();
        Http::fake(['api.github.com/repos/klant/website/commits/main' => Http::response(str_repeat('b', 40))]);
        $preview = $this->preview(['commit_sha' => str_repeat('a', 40)]);

        $message = app(PreviewUpdates::class)->check();

        $this->assertStringContainsString('bbbbbbb', $message);
        $this->assertSame(PreviewStatus::Pending, $preview->fresh()->status);
        $this->assertSame(str_repeat('b', 40), $preview->fresh()->commit_sha);
        Queue::assertPushed(DeployPreview::class, 1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer github_pat_SECRET'));

        // Same commit (or a build already queued): nothing happens.
        $preview->forceFill(['status' => PreviewStatus::Ready])->save();
        $this->assertNull(app(PreviewUpdates::class)->check());
        Queue::assertPushed(DeployPreview::class, 1);
    }

    public function test_the_update_button_deploys_only_when_there_is_a_new_commit(): void
    {
        Queue::fake();
        $this->actingAs($this->owner());
        $github = Http::response(str_repeat('a', 40));
        Http::fake(function () use (&$github) {
            return $github;
        });
        $preview = $this->preview(['commit_sha' => str_repeat('a', 40)]);

        // Nothing new.
        Livewire::test(ViewPreview::class, ['record' => $preview->getKey()])->callAction('update')
            ->assertNotified('Al bijgewerkt: de nieuwste commit (aaaaaaa) staat live.');
        Queue::assertNothingPushed();

        // New commit: deployed, database kept.
        $github = Http::response(str_repeat('b', 40));
        Livewire::test(ViewPreview::class, ['record' => $preview->getKey()])->callAction('update');
        $this->assertSame(PreviewStatus::Pending, $preview->fresh()->status);
        $this->assertSame(str_repeat('b', 40), $preview->fresh()->commit_sha);
        Queue::assertPushed(DeployPreview::class, 1);

        // A build is queued: the button is disabled.
        Livewire::test(ViewPreview::class, ['record' => $preview->getKey()])->assertActionDisabled('update');

        // GitHub does not answer: a warning, nothing queued.
        $preview->refresh()->forceFill(['status' => PreviewStatus::Ready])->save();
        $github = Http::response('Bad credentials', 401);
        [$result] = app(PreviewUpdates::class)->update($preview->fresh());
        $this->assertSame('unknown', $result);
        Queue::assertPushed(DeployPreview::class, 1);
    }

    public function test_no_automatic_update_when_switched_off_expired_cleared_or_github_fails(): void
    {
        Queue::fake();
        $github = Http::response(str_repeat('c', 40));
        Http::fake(function () use (&$github) {
            return $github;
        });

        $off = $this->preview(['auto_update' => false, 'commit_sha' => str_repeat('a', 40)]);
        $this->assertNull(app(PreviewUpdates::class)->check());

        $off->update(['auto_update' => true, 'expires_at' => now()->subMinute()]);
        $this->assertNull(app(PreviewUpdates::class)->check());

        $off->update(['expires_at' => now()->addDay(), 'status' => PreviewStatus::Cleared]);
        $this->assertNull(app(PreviewUpdates::class)->check());

        $github = Http::response('Not Found', 404);
        $off->update(['status' => PreviewStatus::Ready]);
        $this->assertNull(app(PreviewUpdates::class)->check());

        Queue::assertNothingPushed();
    }

    public function test_the_worker_command_checks_for_updates_and_runs_the_previews_queue(): void
    {
        Http::fake(['api.github.com/*' => Http::response('', 500)]);
        $this->preview();

        $this->artisan('previews:work')->assertSuccessful();
    }

    public function test_strangers_get_a_friendly_403_page(): void
    {
        $page = file_get_contents(resource_path('preview/403.html'));
        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $page);
        $this->assertStringContainsString('Hier wordt nog gebouwd', $page);
        $this->assertStringContainsString('prefers-reduced-motion', $page);
        $this->assertStringNotContainsString('<script', $page);

        $script = file_get_contents(base_path('scripts/preview-deploy.sh'));
        $this->assertStringContainsString('ErrorDocument 403 /__jebbari-403.html', $script);
        $this->assertStringContainsString('RewriteCond %{REQUEST_URI} !^/__jebbari-403\\.html$', $script);
    }

    // ------------------------------------------------------------ client page

    public function test_the_review_page_shows_the_banner_the_frame_and_sets_the_access_cookie(): void
    {
        $preview = $this->preview();

        $response = $this->get('/review/'.$preview->token)->assertOk();

        $response->assertSee('Preview, niet definitief')->assertSee('Bakkerij de Vries')
            ->assertSee('Goedkeuren')->assertSee('Wijzigingen aanvragen')
            ->assertSee('src="https://preview.jebbari.test/"', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('frame-src https://preview.jebbari.test', $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString('unsafe-inline', $response->headers->get('Content-Security-Policy'));

        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'jebbari_preview');
        $this->assertNotNull($cookie);
        $this->assertSame($preview->access_key, $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('lax', $cookie->getSameSite());

        // CSP-clean: no inline styles, handlers or scripts.
        $html = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/\sstyle="/', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+="/', $html);
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\ssrc=)[^>]*>/', $html);
    }

    public function test_the_shared_link_shows_a_preview_with_title_text_and_image(): void
    {
        $preview = $this->preview(['client_name' => 'Sam Ranjkar']);

        $this->get('/review/'.$preview->token)->assertOk()
            ->assertSee('<meta property="og:title" content="Je nieuwe website, Sam Ranjkar: het prototype">', false)
            ->assertSee('Ontworpen en gebouwd door Jebbari.', false)
            ->assertSee('<meta property="og:image" content="'.route('review.share', $preview->token).'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);

        $image = $this->get('/review/'.$preview->token.'/share.png')->assertOk();
        $this->assertSame('image/png', $image->headers->get('Content-Type'));
        $this->assertSame(1200, getimagesize($image->baseResponse->getFile()->getPathname())[0]);

        // A cleared preview shares the plain Jebbari image, without the name.
        $preview->update(['status' => PreviewStatus::Cleared]);
        $this->get('/review/'.$preview->token.'/share.png')->assertRedirect(asset('og/default.png'));
    }

    public function test_other_pages_do_not_frame_the_preview(): void
    {
        $csp = $this->get('/_style')->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('preview.jebbari.test', (string) $csp);
    }

    public function test_the_review_page_follows_the_preview_language(): void
    {
        $preview = $this->preview(['locale' => 'en']);

        $this->get('/review/'.$preview->token)->assertOk()->assertSee('Preview, not final')->assertSee('Approve')->assertSee('<html lang="en">', false);
    }

    public function test_unknown_expired_and_cleared_links_show_no_preview_and_set_no_cookie(): void
    {
        $this->get('/review/'.str_repeat('a', 64))->assertNotFound();
        $this->get('/review/short')->assertNotFound();

        $expired = $this->preview(['expires_at' => now()->subMinute()]);
        $response = $this->get('/review/'.$expired->token)->assertStatus(410)->assertSee('De link is verlopen');
        $this->assertNull(collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'jebbari_preview'));
        $response->assertDontSee('<iframe', false);

        foreach ([PreviewStatus::Cleared, PreviewStatus::Building, PreviewStatus::Failed] as $status) {
            $preview = $this->preview(['status' => $status]);
            $this->get('/review/'.$preview->token)->assertStatus(410)->assertSee('niet meer beschikbaar');
            $this->post('/review/'.$preview->token.'/approve')->assertStatus(410);
        }
    }

    public function test_approving_emails_the_owner_once(): void
    {
        Mail::fake();
        Settings::save('contact', ['email' => 'studio@voorbeeld.test']);
        $preview = $this->preview();

        $this->post('/review/'.$preview->token.'/approve')->assertRedirect('/review/'.$preview->token);
        $this->post('/review/'.$preview->token.'/approve');

        $this->assertNotNull($preview->fresh()->approved_at);
        Mail::assertSent(PreviewApproved::class, 1);
        Mail::assertSent(PreviewApproved::class, fn ($m) => $m->hasTo('studio@voorbeeld.test'));

        $this->get('/review/'.$preview->token)->assertSee('De preview is goedgekeurd.')->assertDontSee('>Goedkeuren<', false);
        $this->get('/review/'.$preview->token)->assertSee('Goedgekeurd op');
    }

    public function test_requesting_changes_needs_text_and_emails_the_owner(): void
    {
        Mail::fake();
        Settings::save('contact', ['email' => 'studio@voorbeeld.test', 'notify_email' => 'inbox@voorbeeld.test']);
        $preview = $this->preview(['approved_at' => now()]);

        $this->post('/review/'.$preview->token.'/changes', ['change_request' => ''])->assertSessionHasErrors('change_request');
        Mail::assertNothingSent();

        $this->post('/review/'.$preview->token.'/changes', ['change_request' => 'Het logo groter, graag.'])->assertRedirect();

        $preview->refresh();
        $this->assertSame('Het logo groter, graag.', $preview->change_request);
        $this->assertNull($preview->approved_at);
        Mail::assertSent(PreviewChangesRequested::class, fn ($m) => $m->hasTo('inbox@voorbeeld.test')
            && str_contains($m->render(), 'Het logo groter, graag.'));

        $this->get('/review/'.$preview->token)->assertSee('Je wijzigingen zijn doorgegeven.');
    }

    public function test_the_review_buttons_need_csrf_and_are_rate_limited(): void
    {
        $preview = $this->preview();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/review/'.$preview->token.'/changes', ['change_request' => 'Wijziging '.$i]);
        }

        $this->post('/review/'.$preview->token.'/changes', ['change_request' => 'Nog een'])->assertStatus(429);

        $html = $this->get('/review/'.$preview->token)->getContent();
        $this->assertSame(2, substr_count($html, 'name="_token"'));
    }

    // ------------------------------------------------------------ the script

    public function test_the_deploy_script_is_valid_bash_and_guards_its_target(): void
    {
        $script = base_path('scripts/preview-deploy.sh');
        exec('bash -n '.escapeshellarg($script).' 2>&1', $out, $code);
        $this->assertSame(0, $code, implode("\n", $out));

        $source = file_get_contents($script);
        $this->assertStringContainsString('set -euo pipefail', $source);
        $this->assertStringContainsString('git clone --depth 1 --single-branch --branch "$BRANCH" --no-tags -- "$REPO"', $source);
        $this->assertStringContainsString('must end in /httpdocs', $source);
        $this->assertStringContainsString('PREVIEW_PATH contains Jebbari itself', $source);
        $this->assertStringContainsString('unset APP_KEY', $source);
        $this->assertStringNotContainsString('eval ', $source);
    }
}

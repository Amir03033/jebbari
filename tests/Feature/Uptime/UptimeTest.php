<?php

namespace Tests\Feature\Uptime;

use App\Models\Project;
use App\Models\UptimeCheck;
use App\Scan\Fetched;
use App\Scan\Fetcher;
use App\Scan\FetchFailed;
use App\Uptime\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\BuildsSiteContent;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class UptimeTest extends TestCase
{
    use BuildsSiteContent, PublishesSite, RefreshDatabase;

    private function project(array $o = []): Project
    {
        return $this->makeProject($o + [
            'title' => ['nl' => 'Live project', 'en' => 'Live project'],
            'url' => 'https://bakkerij.example',
            'monitor' => true,
            'slug_nl' => 'live-project',
            'slug_en' => 'live-project',
        ]);
    }

    private function fetcher(callable $answer): void
    {
        $fetcher = Mockery::mock(Fetcher::class);
        $fetcher->shouldReceive('get')->andReturnUsing($answer);
        $this->app->instance(Fetcher::class, $fetcher);
    }

    private function history(Project $p, int $ok, int $down, int $ms = 200): void
    {
        foreach (range(1, $ok + $down) as $i) {
            UptimeCheck::create(['project_id' => $p->id, 'ok' => $i <= $ok, 'status' => $i <= $ok ? 200 : 503, 'ms' => $i <= $ok ? $ms : null, 'checked_at' => now()->subMinutes($i * 5)]);
        }
    }

    public function test_the_command_measures_only_published_monitored_projects(): void
    {
        $this->publishSite();
        $live = $this->project();
        $this->project(['monitor' => false, 'slug_nl' => 'b', 'slug_en' => 'b']);
        $this->project(['slug_nl' => 'c', 'slug_en' => 'c'])->unpublish();
        $this->fetcher(fn () => new Fetched('https://bakkerij.example', 200, [], '<html>', [], 180));

        $this->artisan('uptime:check')->expectsOutputToContain('1 project(s) checked.');

        $check = UptimeCheck::sole();
        $this->assertSame([$live->id, true, 200, 180], [$check->project_id, $check->ok, $check->status, $check->ms]);
    }

    public function test_a_site_that_does_not_answer_is_recorded_as_down(): void
    {
        $this->publishSite();
        $p = $this->project();
        $this->fetcher(fn () => throw new FetchFailed('timeout'));

        $check = app(Monitor::class)->check($p);

        $this->assertFalse($check->ok);
        $this->assertNull($check->ms);
    }

    public function test_nothing_is_shown_until_there_are_enough_measurements(): void
    {
        $this->publishSite();
        $p = $this->project();
        $this->history($p, Monitor::MIN_CHECKS - 1, 0);

        $this->assertNull(Monitor::summary($p));
        $this->get('/nl/werk/live-project')->assertOk()->assertDontSee('Live gemeten');
    }

    public function test_the_case_study_shows_measured_uptime_and_response_time(): void
    {
        $this->publishSite();
        $p = $this->project();
        $this->history($p, 399, 1, 240);

        $summary = Monitor::summary($p);
        $this->assertSame(99.75, $summary['uptime']);
        $this->assertSame(240, $summary['ms']);
        $this->assertTrue($summary['online']);

        $this->get('/nl/werk/live-project')->assertOk()
            ->assertSee('Live gemeten')
            ->assertSee('Nu online')
            ->assertSee('99,75% uptime')
            ->assertSee('gemiddeld 240 ms')
            ->assertSee('400 metingen');

        $this->get('/en/work/live-project')->assertOk()->assertSee('99.75% uptime');
    }

    public function test_switching_monitoring_off_hides_the_numbers(): void
    {
        $this->publishSite();
        $p = $this->project();
        $this->history($p, 30, 0);
        $p->forceFill(['monitor' => false])->save();

        $this->assertNull(Monitor::summary($p->fresh()));
    }

    public function test_one_failed_check_is_not_reported_as_down_but_two_are(): void
    {
        $this->publishSite();
        $p = $this->project();
        $this->history($p, 30, 0);

        UptimeCheck::create(['project_id' => $p->id, 'ok' => false, 'checked_at' => now()]);
        cache()->flush();
        $this->assertNull(Monitor::summary($p)['online']);

        UptimeCheck::create(['project_id' => $p->id, 'ok' => false, 'checked_at' => now()->addSecond()]);
        cache()->flush();
        $this->assertFalse(Monitor::summary($p)['online']);
    }
}

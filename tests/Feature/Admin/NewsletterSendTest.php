<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Newsletters\Pages\EditNewsletter;
use App\Jobs\SendNewsletter;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Support\BuildsSiteContent;
use Tests\Support\CreatesUsers;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class NewsletterSendTest extends TestCase
{
    use BuildsSiteContent, CreatesUsers, PublishesSite, RefreshDatabase;

    private function newsletter(array $o = []): Newsletter
    {
        return Newsletter::create($o + [
            'subject' => ['nl' => 'Nieuw: ServerScope 2.0', 'en' => 'New: ServerScope 2.0'],
            'body' => ['nl' => '<p>Hallo <strong>lezer</strong>.</p>', 'en' => '<p>Hello reader.</p>'],
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
        Mail::fake();
    }

    public function test_it_goes_to_confirmed_subscribers_only_in_their_language(): void
    {
        $nl = Subscriber::create(['email' => 'nl@example.test', 'locale' => 'nl', 'confirmed_at' => now()]);
        $en = Subscriber::create(['email' => 'en@example.test', 'locale' => 'en', 'confirmed_at' => now()]);
        Subscriber::create(['email' => 'wacht@example.test', 'locale' => 'nl']);
        Subscriber::create(['email' => 'weg@example.test', 'locale' => 'nl', 'confirmed_at' => now(), 'unsubscribed_at' => now()]);
        $this->actingAs($this->owner());
        $n = $this->newsletter();

        Livewire::test(EditNewsletter::class, ['record' => $n->getRouteKey()])->callAction('send');

        $this->assertSame(2, $n->fresh()->recipients);
        $this->assertTrue($n->fresh()->isSent());
        Mail::assertSent(NewsletterMail::class, 2);
        Mail::assertSent(NewsletterMail::class, fn (NewsletterMail $m) => $m->hasTo('en@example.test') && $m->envelope()->subject === 'New: ServerScope 2.0');
        Mail::assertSent(NewsletterMail::class, function (NewsletterMail $m) use ($nl) {
            $html = $m->render();

            return $m->hasTo('nl@example.test') && str_contains($html, 'lezer') && str_contains($html, 'newsletter/unsubscribe/'.$nl->id)
                && isset($m->headers()->text['List-Unsubscribe']);
        });
        Mail::assertNotSent(NewsletterMail::class, fn ($m) => $m->hasTo('wacht@example.test') || $m->hasTo('weg@example.test'));
    }

    public function test_missing_english_falls_back_to_dutch(): void
    {
        $n = $this->newsletter(['subject' => ['nl' => 'Alleen NL', 'en' => ''], 'body' => ['nl' => '<p>Tekst</p>', 'en' => '']]);

        $this->assertSame('Alleen NL', $n->in('en')['subject']);
    }

    public function test_someone_who_unsubscribes_while_it_is_queued_gets_nothing(): void
    {
        $s = Subscriber::create(['email' => 'a@example.test', 'locale' => 'nl', 'confirmed_at' => now()]);
        $job = new SendNewsletter($this->newsletter(), $s);
        $s->update(['unsubscribed_at' => now()]);

        $job->handle();

        Mail::assertNothingSent();
    }

    public function test_an_article_adds_a_read_button_and_a_sent_newsletter_is_locked(): void
    {
        $post = $this->makePost(['slug_nl' => 'mijn-artikel', 'slug_en' => 'my-article']);
        $n = $this->newsletter(['post_id' => $post->id, 'sent_at' => now()]);

        $html = (new NewsletterMail($n, null, 'nl'))->render();
        $this->assertStringContainsString('/nl/blog/mijn-artikel', $html);
        $this->assertStringContainsString('Lees het artikel', $html);

        $this->actingAs($this->owner());
        Livewire::test(EditNewsletter::class, ['record' => $n->getRouteKey()])
            ->assertActionHidden('send')
            ->call('save')->assertForbidden();
    }

    public function test_the_owner_can_send_a_test_mail_and_editors_cannot_open_it(): void
    {
        $this->actingAs($owner = $this->owner());
        $n = $this->newsletter();

        Livewire::test(EditNewsletter::class, ['record' => $n->getRouteKey()])->callAction('test');
        Mail::assertSent(NewsletterMail::class, fn ($m) => $m->hasTo($owner->email));
        $this->assertFalse($n->fresh()->isSent());

        $this->actingAs($this->editor())->get('/admin/newsletters')->assertForbidden();
    }
}

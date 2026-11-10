<?php

namespace Tests\Feature\Site;

use App\Enums\InquiryStatus;
use App\Mail\NewInquiry;
use App\Models\Inquiry;
use App\Support\Settings;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\PublishesSite;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use PublishesSite, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publishSite();
        RateLimiter::clear('contact');
        Cache::flush();
    }

    /** Posts the form with a fresh rate-limit window (the limit itself is tested separately). */
    private function submit(string $uri, array $data)
    {
        Cache::flush();

        return $this->post($uri, $data);
    }

    private function fromPage(string $from, string $uri, array $data)
    {
        Cache::flush();

        return $this->from($from)->post($uri, $data);
    }

    /** A valid submission, with a form token that is old enough to look human. */
    private function payload(array $o = []): array
    {
        return $o + [
            'name' => 'Sanne de Vries', 'email' => 'sanne@example.com', 'company' => 'De Vries Training', 'budget' => '7.5-15k',
            'message' => 'Wij zoeken een boekingssysteem voor onze cursussen.', 'website' => '',
            '_t' => Crypt::encryptString((string) (time() - 30)),
        ];
    }

    public function test_the_form_renders_with_labels_a_honeypot_and_a_time_token_in_both_languages(): void
    {
        $nl = $this->get('/nl/contact')->assertOk();
        $nl->assertSee('Verstuur bericht')->assertSee('Wat wil je laten bouwen?')->assertSee('name="website"', false)->assertSee('name="_t"', false)->assertSee('name="_token"', false);
        $html = $nl->getContent();
        $this->assertMatchesRegularExpression('#<label for="f-name"#', $html);
        $this->assertStringContainsString('autocomplete="email"', $html);

        $this->get('/en/contact')->assertSee('Send message')->assertSee('What do you want to build?');
    }

    public function test_a_valid_submission_is_stored_in_the_cms_inbox_as_unread(): void
    {
        $this->submit('/nl/contact', $this->payload())->assertRedirect(url('/nl/contact').'#contact-form')->assertSessionHas('inquiry_sent');

        $inquiry = Inquiry::firstOrFail();
        $this->assertSame('Sanne de Vries', $inquiry->name);
        $this->assertSame('sanne@example.com', $inquiry->email);
        $this->assertSame('De Vries Training', $inquiry->company);
        $this->assertSame('7.5-15k', $inquiry->budget);
        $this->assertSame('nl', $inquiry->locale);
        $this->assertSame(InquiryStatus::New, $inquiry->status);
        $this->assertNull($inquiry->read_at);
        $this->assertSame(64, strlen($inquiry->ip_hash));
        $this->assertNotSame('127.0.0.1', $inquiry->ip_hash, 'the address itself is never stored');
        $this->assertSame($inquiry->ip_hash, hash_hmac('sha256', '127.0.0.1', config('app.key')));
    }

    public function test_the_english_form_stores_the_language(): void
    {
        $this->submit('/en/contact', $this->payload())->assertRedirect(url('/en/contact').'#contact-form');

        $this->assertSame('en', Inquiry::firstOrFail()->locale);
    }

    public function test_the_confirmation_replaces_the_form(): void
    {
        $this->followingRedirects()->submit('/nl/contact', $this->payload())->assertSee('Bedankt. Je bericht is aangekomen.')->assertSee('Ik reageer binnen één werkdag.')
            ->assertDontSee('Verstuur bericht');
        $this->followingRedirects()->submit('/en/contact', $this->payload())->assertSee('Thank you. Your message has arrived.');

        // Reloading shows the form again.
        $this->get('/nl/contact')->assertSee('Verstuur bericht')->assertDontSee('Bedankt. Je bericht');
    }

    public function test_validation_errors_redirect_back_without_storing_anything(): void
    {
        $this->fromPage('/nl/contact', '/nl/contact', $this->payload(['name' => '', 'email' => 'geen-email', 'message' => 'kort']))
            ->assertRedirect('/nl/contact')->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(0, Inquiry::count());
    }

    public function test_submitted_values_are_kept_when_there_are_errors(): void
    {
        $this->fromPage('/nl/contact', '/nl/contact', $this->payload(['email' => 'nee', 'company' => 'Bewaard BV', 'name' => 'Bewaarde Naam']));

        $this->get('/nl/contact')->assertSee('value="Bewaard BV"', false)->assertSee('value="Bewaarde Naam"', false);
    }

    public function test_dutch_and_english_error_messages_and_accessible_error_markup(): void
    {
        $this->fromPage('/nl/contact', '/nl/contact', $this->payload(['email' => 'nee', 'name' => '']));
        $nl = $this->get('/nl/contact')->getContent();
        $this->assertStringContainsString('Vul een geldig e-mailadres in.', $nl);
        $this->assertStringContainsString('Vul je naam in.', $nl);
        $this->assertStringContainsString('aria-invalid="true"', $nl);
        $this->assertStringContainsString('aria-describedby="f-email-error"', $nl);
        $this->assertStringContainsString('id="f-email-error"', $nl);
        $this->assertStringContainsString('role="alert"', $nl);

        $this->fromPage('/en/contact', '/en/contact', $this->payload(['email' => 'no', 'message' => '']));
        $en = $this->get('/en/contact')->getContent();
        $this->assertStringContainsString('Please enter a valid email address.', $en);
        $this->assertStringContainsString('Please fill in your message.', $en);
    }

    public function test_every_required_field_is_enforced_and_lengths_are_capped(): void
    {
        foreach (['name', 'email', 'message', '_t'] as $field) {
            $this->submit('/nl/contact', $this->payload([$field => '']))->assertSessionHasErrors($field);
        }

        $this->submit('/nl/contact', $this->payload(['message' => str_repeat('x', 5001)]))->assertSessionHasErrors('message');
        $this->submit('/nl/contact', $this->payload(['name' => str_repeat('n', 121)]))->assertSessionHasErrors('name');
        $this->submit('/nl/contact', $this->payload(['budget' => 'een-miljoen']))->assertSessionHasErrors('budget');
        $this->assertSame(0, Inquiry::count());
    }

    public function test_optional_fields_may_be_left_empty(): void
    {
        $this->submit('/nl/contact', $this->payload(['company' => '', 'budget' => '']))->assertSessionHasNoErrors();

        $inquiry = Inquiry::firstOrFail();
        $this->assertNull($inquiry->company);
        $this->assertNull($inquiry->budget);
    }

    public function test_the_honeypot_silently_discards_bot_submissions(): void
    {
        $this->submit('/nl/contact', $this->payload(['website' => 'https://spam.example']))
            ->assertRedirect(url('/nl/contact').'#contact-form')->assertSessionHas('inquiry_sent')->assertSessionHasNoErrors();

        $this->assertSame(0, Inquiry::count(), 'nothing is stored, and the bot is told it worked');
    }

    public function test_submissions_that_are_too_fast_are_rejected(): void
    {
        $this->submit('/nl/contact', $this->payload(['_t' => Crypt::encryptString((string) time())]))->assertSessionHasErrors('message');
        $this->assertSame(0, Inquiry::count());
    }

    public function test_forged_or_expired_form_tokens_are_rejected(): void
    {
        $this->submit('/nl/contact', $this->payload(['_t' => 'niet-versleuteld']))->assertSessionHasErrors('message');
        $this->submit('/nl/contact', $this->payload(['_t' => Crypt::encryptString((string) (time() - 90000))]))->assertSessionHasErrors('message');
        $this->assertSame(0, Inquiry::count());
    }

    public function test_the_form_is_rate_limited_per_address(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/nl/contact', $this->payload(['email' => "a$i@example.com"]))->assertRedirect();
        }

        $this->post('/nl/contact', $this->payload(['email' => 'a4@example.com']))->assertStatus(429)->assertSee('Te veel verzoeken');
        $this->assertSame(3, Inquiry::count());
    }

    public function test_the_owner_is_notified_by_email_when_an_address_is_configured(): void
    {
        Mail::fake();
        Settings::save('contact', ['email' => 'studio@voorbeeld.test']);

        $this->submit('/nl/contact', $this->payload());

        Mail::assertSent(NewInquiry::class, fn (NewInquiry $m) => $m->hasTo('studio@voorbeeld.test') && $m->hasReplyTo('sanne@example.com'));
    }

    public function test_a_separate_notification_address_wins(): void
    {
        Mail::fake();
        Settings::save('contact', ['email' => 'studio@voorbeeld.test', 'notify_email' => 'inbox@voorbeeld.test']);

        $this->submit('/nl/contact', $this->payload());

        Mail::assertSent(NewInquiry::class, fn ($m) => $m->hasTo('inbox@voorbeeld.test'));
    }

    public function test_no_email_is_sent_and_nothing_is_lost_when_no_address_is_configured(): void
    {
        Mail::fake();

        $this->submit('/nl/contact', $this->payload())->assertSessionHas('inquiry_sent');

        Mail::assertNothingSent();
        $this->assertSame(1, Inquiry::count());
    }

    public function test_the_inquiry_is_kept_even_when_sending_the_email_fails(): void
    {
        Settings::save('contact', ['email' => 'studio@voorbeeld.test']);
        $this->mock(Mailer::class, function ($mock) {
            $mock->shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));
        });

        $this->submit('/nl/contact', $this->payload())->assertRedirect()->assertSessionHas('inquiry_sent');

        $this->assertSame(1, Inquiry::count());
    }

    public function test_the_notification_email_contains_the_inquiry_and_a_link_to_the_inbox(): void
    {
        $inquiry = Inquiry::create(['name' => 'Jan', 'email' => 'jan@example.com', 'message' => 'Hallo daar', 'budget' => '15k+', 'locale' => 'nl']);

        $html = (new NewInquiry($inquiry))->render();

        $this->assertStringContainsString('Jan', $html);
        $this->assertStringContainsString('jan@example.com', $html);
        $this->assertStringContainsString('Hallo daar', $html);
        $this->assertStringContainsString('/admin/inquiries/'.$inquiry->id, $html);
    }

    public function test_stored_text_is_escaped_not_executed_on_the_inbox_and_mail(): void
    {
        $this->submit('/nl/contact', $this->payload(['name' => '<script>alert(1)</script>', 'message' => 'Hallo <b>vet</b> bericht met script']));

        $inquiry = Inquiry::firstOrFail();
        $this->assertSame('<script>alert(1)</script>', $inquiry->name, 'stored as typed');
        $this->assertStringNotContainsString('<script>', (new NewInquiry($inquiry))->render() === '' ? '' : e($inquiry->name));
    }

    public function test_the_contact_page_shows_the_email_and_booking_link_only_when_configured(): void
    {
        $html = $this->get('/nl/contact')->getContent();
        $this->assertStringNotContainsString('mailto:', $html);
        $this->assertStringNotContainsString('data-copy=', $html);

        Settings::save('contact', ['email' => 'studio@voorbeeld.test', 'booking_url' => 'https://cal.example.test/x']);

        $this->get('/nl/contact')->assertSee('mailto:studio@voorbeeld.test', false)->assertSee('data-copy="studio@voorbeeld.test"', false)
            ->assertSee('Kopieer e-mailadres')->assertSee('https://cal.example.test/x', false);
    }

    public function test_the_contact_page_shows_the_meeting_line_and_reply_promise_from_the_cms(): void
    {
        $this->get('/nl/contact')->assertSee('Afspreken in Zwolle, Amsterdam of online.')->assertSee('Wij reageren binnen één werkdag.');
        $this->get('/en/contact')->assertSee('Meet in Zwolle, Amsterdam or online.')->assertSee('We reply within one working day.');
    }
}

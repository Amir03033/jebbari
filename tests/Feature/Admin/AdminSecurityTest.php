<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\AdminTestCase;
use Tests\Support\PublishesSite;

class AdminSecurityTest extends AdminTestCase
{
    use PublishesSite;

    public function test_guests_are_sent_to_the_admin_login(): void
    {
        foreach (['/admin', '/admin/services', '/admin/inquiries', '/admin/logboek'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    public function test_an_account_without_an_authenticator_must_set_one_up_first(): void
    {
        $user = $this->userWithRole(UserRole::Owner, ['app_authentication_secret' => null]);

        $response = $this->actingAs($user)->get('/admin/services');

        $response->assertRedirect();
        $this->assertStringContainsString('multi-factor-authentication/set-up', $response->headers->get('Location'));
    }

    public function test_inactive_accounts_cannot_use_the_panel(): void
    {
        $user = $this->userWithRole(UserRole::Owner, ['is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_the_login_page_is_dutch(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Inloggen');
    }

    public function test_roles_see_only_what_they_may(): void
    {
        $matrix = [
            'owner' => ['/admin/services' => 200, '/admin/users' => 200, '/admin/logboek' => 200, '/admin/company-settings' => 200, '/admin/inquiries' => 200, '/admin/navigation-items' => 200],
            'editor' => ['/admin/services' => 200, '/admin/users' => 403, '/admin/logboek' => 403, '/admin/company-settings' => 403, '/admin/inquiries' => 200, '/admin/navigation-items' => 403],
            'support' => ['/admin/services' => 403, '/admin/users' => 403, '/admin/logboek' => 403, '/admin/company-settings' => 403, '/admin/inquiries' => 200, '/admin/navigation-items' => 403],
        ];

        foreach ($matrix as $role => $urls) {
            $user = $this->userWithRole(UserRole::from($role));

            foreach ($urls as $url => $status) {
                $this->actingAs($user)->get($url)->assertStatus($status, "$role -> $url");
            }
        }
    }

    public function test_only_the_owner_can_create_users_and_a_new_user_gets_the_chosen_role(): void
    {
        $this->actingAs($this->editor());
        $this->get('/admin/users/create')->assertForbidden();

        $this->actingAs($this->owner());
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Nieuw', 'email' => 'nieuw@example.com', 'role' => 'editor', 'is_active' => true, 'password' => 'een-lang-wachtwoord-123'])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'nieuw@example.com')->firstOrFail();
        $this->assertSame(UserRole::Editor, $user->role);
        $this->assertNull($user->app_authentication_secret, 'the new user must set up MFA at first login');
    }

    public function test_weak_passwords_are_rejected_for_new_users(): void
    {
        $this->actingAs($this->owner());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'X', 'email' => 'x@example.com', 'role' => 'editor', 'password' => 'kort'])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    public function test_the_owner_cannot_delete_their_own_account(): void
    {
        $owner = $this->owner();

        $this->assertFalse($owner->can('delete', $owner));
        $this->assertTrue($owner->can('delete', $this->editor()));
    }

    public function test_secrets_are_hidden_and_encrypted(): void
    {
        $user = $this->owner();

        $this->assertArrayNotHasKey('app_authentication_secret', $user->toArray());
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $user->getRawOriginal('app_authentication_secret'));
    }

    public function test_failed_logins_are_audited(): void
    {
        $user = $this->owner();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'fout-wachtwoord'])
            ->call('authenticate')
            ->assertHasFormErrors();

        $log = Activity::query()->where('event', 'login_failed')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($user->email, $log->properties['email']);
    }

    public function test_admin_and_public_pages_get_different_security_headers(): void
    {
        $admin = $this->actingAs($this->owner())->get('/admin');
        $this->assertStringContainsString("'unsafe-eval'", $admin->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('noindex', $admin->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('no-store', $admin->headers->get('Cache-Control'));

        auth()->logout();
        $this->publishSite(['home', 'work']);
        $public = $this->get('/en/work');
        $csp = $public->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertSame('nosniff', $public->headers->get('X-Content-Type-Options'));
        $this->assertNull($public->headers->get('X-Robots-Tag'));
    }

    public function test_robots_txt_hides_the_admin_and_previews(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /preview', $robots);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $robots);
    }

    public function test_public_pages_load_no_filament_or_livewire_assets(): void
    {
        // Livewire keeps a static "a component rendered" flag that would leak in from earlier
        // tests in this process; in production every request is a fresh process.
        SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;

        $this->publishSite(['home']);
        $html = $this->get('/nl')->assertOk()->getContent();

        $this->assertStringNotContainsString('livewire', strtolower($html));
        $this->assertStringNotContainsString('filament', strtolower($html));
    }
}

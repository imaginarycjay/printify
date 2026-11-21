<?php

namespace Tests\Feature\Auth;

use App\Models\PrintShop;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('print shop owners')
            ->assertSee('Continue with Google')
            ->assertSee('data-test="google-login-button"', false)
            ->assertSee(route('register'), false)
            ->assertSee(route('owner.register'), false)
            ->assertSee('data-test="login-button"', false)
            ->assertDontSee('h-1 w-full bg-amber-500', false)
            ->assertDontSee('Sign in with a passkey')
            ->assertDontSee('Dev quick login')
            ->assertDontSee('dev-login')
            ->assertDontSee('Precision Print Architecture')
            ->assertDontSee('Business Service Configurator');

        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new DOMXPath($document);

        expect($xpath->evaluate('count(//a[@*[name()="wire:navigate"]])'))->toBe(0.0);
    }

    public function test_login_with_unknown_account_displays_contextual_auth_feedback_and_toast(): void
    {
        $response = $this->followingRedirects()->from(route('login'))->post(route('login.store'), [
            'email' => 'missing-account@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertOk()
            ->assertSee('data-test="auth-feedback"', false)
            ->assertSee('Account not found or wrong password')
            ->assertSee('toast-show', false);
    }

    public function test_login_with_suspended_shop_displays_suspension_reason_in_auth_feedback(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_BUSINESS_OWNER]);
        PrintShop::create([
            'user_id' => $owner->id,
            'name' => 'Suspended Shop',
            'is_setup_completed' => true,
            'approval_status' => PrintShop::STATUS_SUSPENDED,
            'rejection_reason' => 'Repeated late order dispatch.',
        ]);

        $response = $this->followingRedirects()->from(route('login'))->post(route('login.store'), [
            'email' => $owner->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertSee('data-test="auth-feedback"', false)
            ->assertSee('Print shop suspended')
            ->assertSee('Repeated late order dispatch.')
            ->assertSee('toast-show', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }
}

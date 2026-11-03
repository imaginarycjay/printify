<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_appearance_settings_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('appearance.edit'));

        $response->assertOk();
        $response->assertSee('Appearance');
        $response->assertSee('Light');
        $response->assertSee('Dark');
        $response->assertSee('System');
    }

    public function test_appearance_settings_page_component_can_be_tested_via_livewire(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::settings.appearance')
            ->assertOk()
            ->assertSee('Light')
            ->assertSee('Dark')
            ->assertSee('System');
    }

    public function test_theme_toggle_is_rendered_on_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('$flux.dark', false);
    }
}

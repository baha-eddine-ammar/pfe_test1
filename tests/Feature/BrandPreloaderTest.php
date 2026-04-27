<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandPreloaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_preloader_markup_is_rendered_on_public_guest_and_authenticated_pages(): void
    {
        $user = User::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-brand-preloader', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-brand-preloader', false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-brand-preloader', false);
    }

    public function test_preloader_markup_is_not_rendered_on_launch_page(): void
    {
        $this->get(route('launch'))
            ->assertOk()
            ->assertDontSee('data-brand-preloader', false);
    }

    public function test_preloader_can_be_disabled_via_configuration(): void
    {
        config()->set('preloader.enabled', false);
        $user = User::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-brand-preloader', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('data-brand-preloader', false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-brand-preloader', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontpageTest extends TestCase
{
    #[Test]
    public function user_can_load_front_page()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    #[Test]
    public function front_page_shows_configured_tagline()
    {
        config(['app.tagline' => 'Nordic Training Administration']);

        $response = $this->get('/');
        $response->assertSee('Nordic Training Administration');
    }

    #[Test]
    public function user_gets_redirect_if_logged_in()
    {
        $user = User::factory()->make();
        Auth::login($user);

        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    #[Test]
    public function user_cant_logout_if_not_logged_in()
    {
        $response = $this->get('/logout');
        $response->assertRedirect('/login');
    }

    #[Test]
    public function test_director_sees_user_search_in_topbar(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $response = $this->actingAs($director)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Search for user');
    }

    public function test_regular_user_does_not_see_user_search_in_topbar(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Search for user');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FrontpageTest extends TestCase
{

    /** @test **/
    public function user_can_load_front_page()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /** @test **/
    public function user_gets_redirect_if_logged_in()
    {

        $user = User::factory()->make();
        Auth::login($user);

        $response = $this->get('/');
        $response->assertRedirect('/dashboard');

    }

    /** @test */
    public function user_cant_logout_if_not_logged_in()
    {
        $response = $this->get('/logout');
        $response->assertRedirect('/login');

    }


}

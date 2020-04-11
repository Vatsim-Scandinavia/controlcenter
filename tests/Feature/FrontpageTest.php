<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class FrontpageTest extends TestCase
{

    use RefreshDatabase;

    /** @test **/
    public function user_can_load_front_page()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /** @test **/
    public function user_gets_redirect_if_logged_in()
    {

        $user = factory(User::class)->make();
        Auth::login($user);

        $response = $this->get('/');
        $response->assertStatus(302)
            ->assertRedirect('/dashboard');

    }


}

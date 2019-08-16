<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function test_right_registration_blade_returned()
    {
        $response = $this->get('/register');
        $response->assertSuccessful();
        $response->assertViewIs('auth.register');
    }

    /** @test */
    function test_user_can_register_successfully_and_be_logged_in_automatically()
    {
        $response = $this->post('/register', [
            'name' => 'Richard Opoku',
            'email' => 'richard@tutuka.com',
            'password' => 'S0m3T0ughP@$$w0rd',
            'password_confirmation' => 'S0m3T0ughP@$$w0rd',
        ]);

        $response->assertRedirect('/home');
    }

    /** @test */
    function test_user_registration_fails_with_unmatched_passwords()
    {
        $response = $this->post('/register', [
            'name' => 'Richard Opoku',
            'email' => 'richard@tutuka.com',
            'password' => 'S0m3T0ughP@$$w0rd',
            'password_confirmation' => 'SomeToughPassword',
        ]);

        $response->assertRedirect('/');
    }

    /** @test */
    function test_user_registration_fails_due_to_wrong_email()
    {
        $response = $this->post('/register', [
            'name' => 'Richard Opoku',
            'email' => 'richardopoku.com',
            'password' => 'S0m3T0ughP@$$w0rd',
            'password_confirmation' => 'S0m3T0ughP@$$w0rd',
        ]);

        $response->assertRedirect('/');
    }

    /** @test */
    function test_guest_can_view_the_login_form()
    {
        $response = $this->get('/login');

        $response->assertSuccessful();

        $response->assertViewIs('auth.login');
    }

    /** @test */
    function test_authenticated_user_cannot_view_the_login_page()
    {
        $user = factory(User::class)->make();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/home');
    }

    /** @test */
    function test_user_can_login_with_correct_credentials()
    {
        $user = factory(User::class)->create([
            'password' => bcrypt($password = 'S0m3T0ughP@$$w0rd'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    function test_registered_user_cannot_login_with_incorrect_password()
    {
        $user = factory(User::class)->create([
            'password' => bcrypt('S0m3T0ughP@$$w0rd'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'SomeToughPassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }
}

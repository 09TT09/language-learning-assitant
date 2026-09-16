<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_through_the_api(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user',
                'token',
            ])
            ->assertJsonPath('user.id', $user->id);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test-device',
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }


    public function test_api_login_requires_email_password_and_device_name(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
                'device_name',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_login_rejects_invalid_email_format(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password',
            'device_name' => 'test-device',
        ]);
    
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_logout_through_the_api(): void
    {
        $user = User::factory()->create();
    
        $token = $user
            ->createToken('test-device')
            ->plainTextToken;
    
        $response = $this
            ->withToken($token)
            ->postJson('/api/logout');
    
        $response->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);
    
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_revoked_token_cannot_access_protected_api_routes(): void
    {
        $user = User::factory()->create();
    
        $token = $user
            ->createToken('test-device')
            ->plainTextToken;
    
        $this
            ->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();
    
        Auth::forgetGuards();
    
        $response = $this
            ->withToken($token)
            ->getJson('/api/user');
    
        $response->assertUnauthorized();
    }
}

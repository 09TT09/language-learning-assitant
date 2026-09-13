<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('chat'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_chat_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('chat'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('chat'));
    }
}

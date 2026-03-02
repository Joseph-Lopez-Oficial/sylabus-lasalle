<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_professor_cannot_access_admin_routes(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);

        $response = $this->actingAs($professor)->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_professor_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('professor.dashboard'));

        $response->assertOk();
    }

    public function test_professor_can_access_professor_routes(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);

        $response = $this->actingAs($professor)->get(route('professor.dashboard'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_professor_routes(): void
    {
        $response = $this->get(route('professor.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_is_redirected_to_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_professor_is_redirected_to_professor_dashboard_after_login(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);

        $response = $this->post(route('login.store'), [
            'email' => $professor->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('professor.dashboard'));
    }
}

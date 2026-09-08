<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dos personas de distinto rol se turnan el mismo navegador —lo habitual en una
 * sala de cómputo—. Sin lo que aquí se prueba, la dirección que dejó pendiente
 * la sesión anterior manda a cada una al panel de la otra: el administrador ve
 * el módulo del profesor y el profesor recibe un 403.
 */
class RoleAwareLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_lands_on_the_admin_panel_after_a_professor_used_the_browser(): void
    {
        $professor = User::factory()->create(['role' => 'professor']);
        $admin = User::factory()->create(['role' => 'admin']);

        // El profesor deja su panel como dirección pendiente en la sesión.
        $this->actingAs($professor)->get(route('professor.dashboard'))->assertOk();
        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_professor_lands_on_the_professor_panel_after_an_admin_used_the_browser(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $professor = User::factory()->create(['role' => 'professor']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => $professor->email,
            'password' => 'password',
        ])->assertRedirect(route('professor.dashboard'));
    }

    public function test_a_pending_address_does_not_override_the_panel_of_the_role(): void
    {
        // Entrar directo a una ruta ajena guarda esa dirección como pendiente;
        // aun así el acceso debe llevar al panel que corresponde al rol.
        $professor = User::factory()->create(['role' => 'professor']);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $professor->email,
            'password' => 'password',
        ])->assertRedirect(route('professor.dashboard'));
    }
}

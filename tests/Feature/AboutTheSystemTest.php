<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

// El diálogo «acerca del sistema» muestra la versión desplegada. Antes se leía
// de package.json, que es del frontend y no viaja al servidor: allí el diálogo
// salía con un guion en lugar del número.

test('the version travels to the page', function () {
    config(['app.version' => '2.2.1']);

    $shared = (new HandleInertiaRequests)->share(Request::create('/'));

    expect($shared['version'])->toBe('2.2.1');
});

test('the version comes from the environment when the frontend manifest is absent', function () {
    // Así es en el servidor: el paquete de producción no lleva package.json.
    $config = require config_path('app.php');

    expect($config['version'])->not->toBeEmpty();
});

test('the dialog shows the version to someone signed in', function () {
    config(['app.version' => '9.9.9']);

    $user = User::factory()->create(['role' => 'professor']);

    $this->actingAs($user)
        ->get(route('professor.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('version', '9.9.9'));
});

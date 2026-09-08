<?php

use Illuminate\Support\Facades\Route;

// El servidor de la universidad sirve la aplicación desde
// https://ensrural.lasalle.edu.co/ingenieria/sylabus, una subcarpeta que
// comparte dominio con otros proyectos de la facultad. Apache no le pasa ese
// prefijo a Laravel, así que sin lo que aquí se prueba las rutas salen desde
// la raíz del dominio e invaden lo que viva allí.

const PRODUCTION_URL = 'https://ensrural.lasalle.edu.co/ingenieria/sylabus';

/**
 * Arranca el proveedor con una dirección dada, como ocurre en el servidor.
 */
function bootWithAppUrl(string $url): void
{
    config(['app.url' => $url]);

    (new App\Providers\AppServiceProvider(app()))->boot();
}

test('the generated routes carry the subdirectory the application is served from', function () {
    bootWithAppUrl(PRODUCTION_URL);

    expect(route('login'))
        ->toBe(PRODUCTION_URL.'/login')
        ->and(url('/dashboard'))
        ->toBe(PRODUCTION_URL.'/dashboard');
});

test('a domain root is left untouched, so local development keeps working', function () {
    // Sin prefijo que forzar, la raíz la resuelve el propio framework a partir
    // de la petición, así que se comprueba lo que importa: que la ruta salga
    // limpia y sin rastro de subcarpeta.
    bootWithAppUrl('http://localhost:8000');

    expect(route('login'))
        ->toEndWith('/login')
        ->not->toContain('/ingenieria');
});

test('every named route of the system carries the prefix', function () {
    bootWithAppUrl(PRODUCTION_URL);

    // La portada apunta a la raíz de la aplicación, que es el prefijo desnudo
    // y por tanto no lleva nada detrás; el resto debe colgar de él.
    $withoutPrefix = collect(Route::getRoutes()->getRoutesByName())
        ->reject(fn ($route): bool => str_contains($route->uri(), '{'))
        ->keys()
        ->reject(fn (string $name): bool => str_starts_with(route($name), PRODUCTION_URL))
        ->values();

    expect($withoutPrefix)->toBeEmpty()
        ->and(route('home'))->toBe(PRODUCTION_URL);
});

test('the routes that take parameters carry the prefix too', function () {
    bootWithAppUrl(PRODUCTION_URL);

    // Las rutas con parámetros construyen la dirección por otro camino que las
    // planas, así que se comprueban aparte: son más de la mitad del sistema.
    expect(route('admin.faculties.edit', 42))
        ->toBe(PRODUCTION_URL.'/admin/faculties/42/edit')
        ->and(route('professor.programmings.grading.show', 7))
        ->toBe(PRODUCTION_URL.'/professor/programmings/7/grading')
        ->and(route('admin.academic-periods.toggle-status', 3))
        ->toBe(PRODUCTION_URL.'/admin/academic-periods/3/toggle-status');
});

test('a route that already carries the prefix does not get it twice', function () {
    bootWithAppUrl(PRODUCTION_URL);

    expect(url(PRODUCTION_URL.'/admin/dashboard'))
        ->toBe(PRODUCTION_URL.'/admin/dashboard')
        ->and(substr_count(route('login'), '/ingenieria/sylabus'))
        ->toBe(1);
});

test('the page announces the prefix so the browser can prepend it too', function () {
    // Los menús de navegación y las migas de pan llevan rutas escritas a mano
    // («/admin/dashboard»), y Wayfinder compila algunas sin prefijo. El
    // navegador las corrige leyendo esta etiqueta, de modo que el formulario
    // de acceso no acabe publicando contra la raíz del dominio.
    config(['app.url' => PRODUCTION_URL]);

    $rendered = view('app', ['page' => ['component' => 'auth/login']])->render();

    expect($rendered)->toContain('<meta name="app-base" content="/ingenieria/sylabus">');
});

test('at the domain root the announced prefix is empty', function () {
    config(['app.url' => 'http://localhost:8000']);

    $rendered = view('app', ['page' => ['component' => 'auth/login']])->render();

    expect($rendered)->toContain('<meta name="app-base" content="">');
});

test('the forced root survives a trailing slash in the configured url', function () {
    bootWithAppUrl(PRODUCTION_URL.'/');

    expect(route('login'))
        ->not->toContain('//login')
        ->and(route('login'))
        ->toBe(PRODUCTION_URL.'/login');
});

test('the assets are served from the subdirectory as well', function () {
    // Sin esto el navegador pide los archivos compilados a la raíz del dominio
    // y la interfaz queda sin estilos ni comportamiento.
    config([
        'app.url' => PRODUCTION_URL,
        'app.asset_url' => PRODUCTION_URL,
    ]);

    expect(asset('build/assets/app.js'))
        ->toBe(PRODUCTION_URL.'/build/assets/app.js');
});

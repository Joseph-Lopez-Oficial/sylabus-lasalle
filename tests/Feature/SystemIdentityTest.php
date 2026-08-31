<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the application is named after the system, not the template', function () {
    expect(config('app.name'))->toBe('Sylabus LaSalle');
});

test('the name reaches every page, so the browser title says it', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('name', 'Sylabus LaSalle'));
});

test('the released version travels with every page', function () {
    // The About panel shows it, and it must match what was published rather
    // than a number written by hand somewhere else.
    $published = json_decode((string) file_get_contents(base_path('package.json')), true)['version'];

    $this->actingAs(User::factory()->create(['role' => 'professor']))
        ->get(route('professor.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('version', $published));
});

test('no screen is left announcing the starter template', function () {
    $offenders = [];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('js'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        // Wayfinder writes the framework's own class names into its generated
        // routes; those are references, not text anybody reads on screen.
        if (! $file->isFile()
            || ! in_array($file->getExtension(), ['tsx', 'ts'], true)
            || str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR)
            || str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'actions'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        foreach (['Laravel Starter Kit', 'laracasts.com', 'cloud.laravel.com', 'laravel.com/docs'] as $trace) {
            if (str_contains($contents, $trace)) {
                $offenders[] = $file->getFilename().' → '.$trace;
            }
        }
    }

    expect($offenders)->toBeEmpty();
});

test('the welcome page of the template is gone and the root still routes', function () {
    expect(file_exists(resource_path('js/pages/welcome.tsx')))->toBeFalse();

    // Whoever arrives without a session lands on the login screen.
    $this->get('/')->assertRedirect(route('login'));
});

test('an admin reaching the root goes to their dashboard', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->get('/')
        ->assertRedirect(route('admin.dashboard'));
});

test('two-factor authentication is no longer offered', function () {
    expect(\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::twoFactorAuthentication()))
        ->toBeFalse()
        ->and(\Illuminate\Support\Facades\Route::has('two-factor.show'))->toBeFalse()
        ->and(\Illuminate\Support\Facades\Route::has('two-factor.login'))->toBeFalse();
});

test('the columns of the withdrawn feature stay in the schema', function () {
    // Production is reached over FTP with no console, so a migration cannot be
    // run there and the dump already carries these columns. Dropping them here
    // would leave the deployed database out of step with the code.
    foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $column) {
        expect(\Illuminate\Support\Facades\Schema::hasColumn('users', $column))
            ->toBeTrue("La columna {$column} debe conservarse.");
    }
});

test('the theme can be switched from anywhere, not only from settings', function () {
    $header = (string) file_get_contents(resource_path('js/components/app-sidebar-header.tsx'));

    // The header is shared by both roles, so one control covers every screen.
    expect($header)->toContain('AppearanceToggle');

    foreach (['admin/admin-layout.tsx', 'professor/professor-layout.tsx'] as $layout) {
        expect((string) file_get_contents(resource_path('js/layouts/'.$layout)))
            ->toContain('AppSidebarHeader');
    }
});

test('the palette carries the institutional colours in both themes', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    // The blue and the gold of the brand manual, as oklch.
    expect(substr_count($css, '0.3037 0.0848 249.45'))->toBeGreaterThanOrEqual(3)
        ->and(substr_count($css, '0.7849 0.1639 77.58'))->toBeGreaterThanOrEqual(2);

    // Nothing should be left neutral: a grey with no chroma is the template's.
    expect($css)->not->toContain('oklch(0.145 0 0)')
        ->and($css)->not->toContain('oklch(0.985 0 0)');
});

test('the icons are the ones of the system', function () {
    // The template's mark is a hexagon path; ours states the institutional blue.
    $favicon = (string) file_get_contents(public_path('favicon.svg'));

    expect($favicon)->toContain('#003057')
        ->and($favicon)->toContain('#F2A900')
        ->and(file_exists(public_path('favicon.ico')))->toBeTrue()
        ->and(file_exists(public_path('apple-touch-icon.png')))->toBeTrue();
});

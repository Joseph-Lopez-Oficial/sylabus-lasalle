<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        $this->configureSubdirectoryUrls();

        Schema::defaultStringLength(191);

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Cuando la aplicación se sirve bajo un subdirectorio (por ejemplo
     * https://dominio/ingenieria/sylabus), Apache no le pasa el prefijo a
     * Laravel, así que las rutas generadas salen desde la raíz del dominio
     * («/login») e invaden lo que viva ahí. Fijar la raíz de URL con el path
     * de APP_URL hace que route(), url() y los formularios de Inertia emitan
     * el prefijo completo. Sin path en APP_URL (desarrollo local) no hace nada.
     */
    protected function configureSubdirectoryUrls(): void
    {
        $url = (string) config('app.url');
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path === '') {
            return;
        }

        // El esquema va aparte: forceRootUrl por sí solo lo deja en http y la
        // página acabaría sirviendo enlaces inseguros desde un sitio https.
        if (parse_url($url, PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }

        URL::forceRootUrl(rtrim($url, '/'));
    }
}

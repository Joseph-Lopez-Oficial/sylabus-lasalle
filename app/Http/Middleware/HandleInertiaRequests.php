<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'version' => $this->releasedVersion(),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The released version of the system, as package.json records it.
     *
     * That file is what semantic-release bumps, so reading it keeps the number
     * shown to the user from drifting away from the one that was published.
     */
    private function releasedVersion(): string
    {
        static $version = null;

        if ($version !== null) {
            return $version;
        }

        $manifest = base_path('package.json');

        $version = is_file($manifest)
            ? (json_decode((string) file_get_contents($manifest), true)['version'] ?? '—')
            : '—';

        return $version;
    }
}

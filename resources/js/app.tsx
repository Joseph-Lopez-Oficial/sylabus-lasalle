import { createInertiaApp, router } from '@inertiajs/react';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Sylabus LaSalle';

// Prefijo cuando la aplicación vive bajo un subdirectorio (ver app.blade.php).
// Wayfinder e Inertia generan rutas desde la raíz («/login»); sin este prefijo
// esas peticiones caen fuera de la carpeta de la aplicación. Se antepone a toda
// ruta absoluta que aún no lo tenga, tanto en las visitas de Inertia como en
// las llamadas directas de Axios. Vacío en la raíz del dominio (desarrollo).
const appBase = document.querySelector<HTMLMetaElement>('meta[name="app-base"]')?.content ?? '';

if (appBase) {
    const withBase = (url: string): string =>
        url.startsWith('/') && !url.startsWith(`${appBase}/`) && url !== appBase ? `${appBase}${url}` : url;

    router.on('before', (event) => {
        const { url } = event.detail.visit;

        if (url.origin === window.location.origin) {
            url.pathname = withBase(url.pathname);
        }
    });

    axios.interceptors.request.use((config) => {
        if (config.url) {
            config.url = withBase(config.url);
        }

        return config;
    });
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

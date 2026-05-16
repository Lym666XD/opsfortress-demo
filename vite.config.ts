import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    // Bind explicitly to IPv4 127.0.0.1 on port 4173 to dodge the Windows
    // Hyper-V dynamic port reservation trap. On Windows, `netsh int ipv4 show
    // excludedportrange protocol=tcp` lists 50-port chunks that Hyper-V
    // reserves at boot; vite's default 5173 falls inside that zone, which
    // produces EACCES errors at startup. Port 4173 (vite's preview default)
    // sits outside the typical reservation cluster (5000-6400). `strictPort`
    // makes vite fail loudly instead of silently picking the next free port.
    server: {
        host: '127.0.0.1',
        port: 4173,
        strictPort: true,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
});

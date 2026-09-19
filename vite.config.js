import { defineConfig } from 'vite';
import { existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const buildDir = 'assets/build';

/**
 * Writes/removes assets/build/hot so Core\Model\Vite can detect the dev
 * server and point <script> tags at it instead of the built manifest,
 * mirroring the Laravel Vite plugin's dev-mode detection without depending
 * on it.
 */
function hotFilePlugin() {
    const hotFile = resolve(process.cwd(), buildDir, 'hot');

    return {
        name: 'omniophp-hot-file',
        configureServer(server) {
            if (!existsSync(resolve(process.cwd(), buildDir))) {
                mkdirSync(resolve(process.cwd(), buildDir), { recursive: true });
            }

            server.httpServer?.once('listening', () => {
                const address = server.httpServer.address();
                const port = typeof address === 'object' && address ? address.port : 5173;
                writeFileSync(hotFile, `http://localhost:${port}`);
            });

            const cleanup = () => {
                if (existsSync(hotFile)) {
                    rmSync(hotFile);
                }
            };

            process.on('exit', cleanup);
            process.on('SIGINT', () => { cleanup(); process.exit(); });
            process.on('SIGTERM', () => { cleanup(); process.exit(); });
        },
    };
}

export default defineConfig({
    plugins: [hotFilePlugin()],
    build: {
        outDir: buildDir,
        manifest: true,
        rollupOptions: {
            input: [
                'resources/assets/js/app.js',
                'resources/assets/css/app.css',
            ],
        },
    },
});

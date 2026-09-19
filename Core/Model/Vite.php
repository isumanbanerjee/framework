<?php

/**
 * Vite Asset Pipeline Helper
 *
 * PHP version 8.1
 *
 * @category  Assets
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use RuntimeException;

/**
 * Vite
 *
 * Renders `<script>`/`<link>` tags for Vite-built frontend assets. In
 * development, points them at the Vite dev server (detected via a `hot`
 * file the dev server writes on start, see vite.config.js) for hot module
 * replacement; in production, resolves versioned filenames from the
 * manifest Vite writes on `npm run build`.
 *
 * Example:
 * ```php
 * $vite = new Vite();
 * echo $vite->tags('resources/assets/js/app.js', 'resources/assets/css/app.css');
 * ```
 *
 * @category  Assets
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @version   1.0.0
 * @since     1.0.0
 */
class Vite
{
    /**
     * Directory containing the Vite build output (manifest.json, hot file).
     *
     * @var string
     */
    private string $buildPath;

    /**
     * Public URL path the built assets are served from.
     *
     * @var string
     */
    private string $publicBase;

    /**
     * @param string|null $buildPath  Directory holding manifest.json/hot (defaults to <cwd>/assets/build).
     * @param string      $publicBase Public URL path the build directory is served under.
     */
    public function __construct(?string $buildPath = null, string $publicBase = '/assets/build')
    {
        $this->buildPath = rtrim($buildPath ?? getcwd() . '/assets/build', '/');
        $this->publicBase = rtrim($publicBase, '/');
    }

    /**
     * Render tags for the given entry points.
     *
     * @param string ...$entries Entry point paths as declared in vite.config.js (e.g. "resources/assets/js/app.js").
     *
     * @return string
     */
    public function tags(string ...$entries): string
    {
        $devServerUrl = $this->devServerUrl();

        return $devServerUrl !== null
            ? $this->devTags($devServerUrl, $entries)
            : $this->manifestTags($entries);
    }

    /**
     * Whether the Vite dev server is currently running (a `hot` file is present).
     *
     * @return bool
     */
    public function isRunningHot(): bool
    {
        return $this->devServerUrl() !== null;
    }

    private function devServerUrl(): ?string
    {
        $hotFile = $this->buildPath . '/hot';

        if (!file_exists($hotFile)) {
            return null;
        }

        $url = trim((string) file_get_contents($hotFile));

        return $url !== '' ? $url : null;
    }

    /**
     * @param string   $serverUrl
     * @param string[] $entries
     *
     * @return string
     */
    private function devTags(string $serverUrl, array $entries): string
    {
        $tags = [sprintf('<script type="module" src="%s/@vite/client"></script>', $serverUrl)];

        foreach ($entries as $entry) {
            $tags[] = sprintf('<script type="module" src="%s/%s"></script>', $serverUrl, $entry);
        }

        return implode("\n", $tags);
    }

    /**
     * @param string[] $entries
     *
     * @return string
     */
    private function manifestTags(array $entries): string
    {
        $manifest = $this->manifest();
        $tags = [];

        foreach ($entries as $entry) {
            if (!isset($manifest[$entry])) {
                throw new RuntimeException("Asset entry \"{$entry}\" not found in Vite manifest.");
            }

            $chunk = $manifest[$entry];

            foreach ($chunk['css'] ?? [] as $css) {
                $tags[] = sprintf('<link rel="stylesheet" href="%s/%s">', $this->publicBase, $css);
            }

            $tags[] = sprintf('<script type="module" src="%s/%s"></script>', $this->publicBase, $chunk['file']);
        }

        return implode("\n", $tags);
    }

    /**
     * @return array<string,array{file:string,css?:array<int,string>}>
     */
    private function manifest(): array
    {
        $manifestPath = $this->buildPath . '/manifest.json';

        if (!file_exists($manifestPath)) {
            throw new RuntimeException("Vite manifest not found at {$manifestPath}. Run \"npm run build\" first.");
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (!is_array($manifest)) {
            throw new RuntimeException("Vite manifest at {$manifestPath} is not valid JSON.");
        }

        return $manifest;
    }
}

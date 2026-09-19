<?php

/**
 * Development Debug Toolbar
 *
 * This file contains the DebugBar class which renders a lightweight
 * request/response inspector for local development, injected into HTML
 * responses when APP_DEBUG is enabled.
 *
 * PHP version 8.1
 *
 * @category  Debugging
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

/**
 * DebugBar Class
 *
 * Collects request, timing, memory, cache, and session diagnostics and
 * renders them as a fixed toolbar for injection into HTML responses.
 * Disabled by default; only active when APP_DEBUG is truthy, so it never
 * appears in a production response.
 *
 * @category  Debugging
 * @package   Core\Model
 * @version   1.0.0
 * @since     1.0.0
 */
class DebugBar
{
    /**
     * Determine whether the toolbar should be rendered.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return filter_var(App::config('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Inject the rendered toolbar into an HTML document.
     *
     * Inserts before the closing </body> tag when present, otherwise
     * appends to the end of the document. Returns the input unchanged
     * when the toolbar is disabled.
     *
     * @param string $html       The HTML document body.
     * @param int    $statusCode The HTTP status code being returned.
     *
     * @return string
     */
    public static function inject(string $html, int $statusCode = 200): string
    {
        if (!self::isEnabled()) {
            return $html;
        }

        $bar = self::render($statusCode);

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $bar . '</body>', $html, 1);
        }

        return $html . $bar;
    }

    /**
     * Render the toolbar markup.
     *
     * @param int $statusCode The HTTP status code being returned.
     *
     * @return string
     */
    public static function render(int $statusCode = 200): string
    {
        $elapsedMs = self::elapsedMilliseconds();
        $memory = self::formatBytes(memory_get_usage());
        $peakMemory = self::formatBytes(memory_get_peak_usage());
        $includedFiles = count(get_included_files());
        $cache = Cache::getGlobalStats();
        $session = self::sessionKeys();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = e($_SERVER['REQUEST_URI'] ?? '');

        $sessionSummary = empty($session)
            ? 'inactive'
            : count($session) . ' key(s): ' . e(implode(', ', $session));

        return <<<HTML
<div id="omniophp-debugbar" style="position:fixed;bottom:0;left:0;right:0;z-index:2147483647;background:#1e1e2e;color:#cdd6f4;font:12px/1.6 monospace;padding:6px 14px;display:flex;flex-wrap:wrap;gap:18px;border-top:2px solid #89b4fa;">
    <span><strong>{$method}</strong> {$uri} &rarr; {$statusCode}</span>
    <span>&#9201; {$elapsedMs} ms</span>
    <span>&#9881; {$memory} (peak {$peakMemory})</span>
    <span>&#128196; {$includedFiles} files</span>
    <span>&#128190; cache: {$cache['hits']} hit / {$cache['misses']} miss / {$cache['writes']} write</span>
    <span>&#128273; session: {$sessionSummary}</span>
</div>
HTML;
    }

    /**
     * Milliseconds elapsed since the request started.
     *
     * @return float
     */
    private static function elapsedMilliseconds(): float
    {
        $start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Keys currently stored in the session, if one is active.
     *
     * @return array<int,string>
     */
    private static function sessionKeys(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }

        return array_keys($_SESSION);
    }

    /**
     * Format a byte count as a human-readable string.
     *
     * @param int $bytes
     *
     * @return string
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float) $bytes;
        $index = 0;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return round($value, 2) . ' ' . $units[$index];
    }
}

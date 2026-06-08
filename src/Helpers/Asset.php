<?php

declare(strict_types=1);

namespace App\Helpers;

final class Asset
{
    private static ?string $baseUrl = null;

    public static function init(string $baseUrl): void
    {
        self::$baseUrl = rtrim($baseUrl, '/') . '/';
    }

    public static function css(string $cdnUrl, string $localPath, string $integrity = ''): string
    {
        $local = self::localUrl($localPath);
        $attrs = ' rel="stylesheet"';
        if ($integrity) {
            $integrityAttr = ' integrity="' . htmlspecialchars($integrity, ENT_QUOTES) . '"';
        } else {
            $integrityAttr = '';
        }

        return <<<HTML
<link href="{$cdnUrl}"{$attrs}{$integrityAttr} crossorigin="anonymous" onerror="this.onerror=null;this.href='{$local}'">
HTML;
    }

    public static function js(string $cdnUrl, string $localPath, string $integrity = '', string $extra = ''): string
    {
        $local = self::localUrl($localPath);
        $attrs = ' type="text/javascript"' . ($extra ? ' ' . $extra : '');
        if ($integrity) {
            $integrityAttr = ' integrity="' . htmlspecialchars($integrity, ENT_QUOTES) . '"';
        } else {
            $integrityAttr = '';
        }

        return <<<HTML
<script src="{$cdnUrl}"{$attrs}{$integrityAttr} crossorigin="anonymous" onerror="this.onerror=null;this.src='{$local}'"></script>
HTML;
    }

    public static function cssLocal(string $localPath): string
    {
        $url = self::localUrl($localPath);
        return '<link href="' . $url . '" rel="stylesheet">';
    }

    public static function jsLocal(string $localPath, string $extra = ''): string
    {
        $url = self::localUrl($localPath);
        $attrs = ' type="text/javascript"' . ($extra ? ' ' . $extra : '');
        return '<script src="' . $url . '"' . $attrs . '></script>';
    }

    private static function localUrl(string $path): string
    {
        $base = self::$baseUrl ?? '/dashboard-se2026/';
        return $base . 'assets/vendor/' . ltrim($path, '/');
    }
}

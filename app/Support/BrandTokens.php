<?php

namespace App\Support;

use LogicException;

/** Nilai tema terang untuk media yang tidak dapat membaca CSS: email dan metadata. */
final class BrandTokens
{
    private static ?array $tokens = null;

    public static function value(string $name): string
    {
        if (self::$tokens === null) {
            $css = file_get_contents(resource_path('css/app.css'));
            preg_match('/:root\s*\{(.*?)\}/s', $css, $root);
            preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $root[1] ?? '', $matches, PREG_SET_ORDER);
            self::$tokens = [];
            foreach ($matches as $match) {
                self::$tokens[$match[1]] = trim($match[2]);
            }
        }

        $value = self::$tokens[$name] ?? throw new LogicException("Token warna tidak ditemukan: {$name}");

        return preg_replace_callback('/var\((--[\w-]+)\)/', fn ($match) => self::value($match[1]), $value);
    }
}

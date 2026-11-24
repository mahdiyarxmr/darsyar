<?php

declare(strict_types=1);

namespace App\Core;

final class Translator
{
    private static string $locale = 'fa';
    private static array $cache = [];

    public static function setLocale(string $locale): void
    {
        $supported = Config::get('app.supported_locales', ['fa', 'en']);
        if (!is_array($supported) || $supported === []) {
            $supported = ['fa', 'en'];
        }
        self::$locale = in_array($locale, $supported, true) ? $locale : 'fa';
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function isRtl(): bool
    {
        $rtl = Config::get('app.rtl_locales', ['fa']);
        return in_array(self::$locale, is_array($rtl) ? $rtl : ['fa'], true);
    }

    public static function direction(): string
    {
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    public static function get(string $key, array $replace = []): string
    {
        $messages = self::loadLocale(self::$locale);
        $text = $messages[$key] ?? null;

        if ($text === null) {
            $fallbackLocale = self::$locale === 'fa' ? 'en' : 'fa';
            $fallback = self::loadLocale($fallbackLocale);
            $text = $fallback[$key] ?? null;
        }

        if ($text === null) {
            $text = $key;
        }

        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }
        return $text;
    }

    public static function allKeys(string $locale): array
    {
        return array_keys(self::loadLocale($locale));
    }

    /**
     * Verifies 100% key parity between Persian (fa) and English (en) translation files.
     *
     * @return array{ok: bool, missing_in_fa: array, missing_in_en: array, empty_in_fa: array, empty_in_en: array, total_fa: int, total_en: int}
     */
    public static function checkConsistency(): array
    {
        $fa = self::loadLocale('fa');
        $en = self::loadLocale('en');
        $faKeys = array_keys($fa);
        $enKeys = array_keys($en);

        $missingInFa = array_values(array_diff($enKeys, $faKeys));
        $missingInEn = array_values(array_diff($faKeys, $enKeys));
        $emptyInFa = array_keys(array_filter($fa, static fn ($v) => trim((string) $v) === ''));
        $emptyInEn = array_keys(array_filter($en, static fn ($v) => trim((string) $v) === ''));

        return [
            'ok' => $missingInFa === [] && $missingInEn === [] && $emptyInFa === [] && $emptyInEn === [],
            'missing_in_fa' => $missingInFa,
            'missing_in_en' => $missingInEn,
            'empty_in_fa' => $emptyInFa,
            'empty_in_en' => $emptyInEn,
            'total_fa' => count($faKeys),
            'total_en' => count($enKeys),
        ];
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    private static function loadLocale(string $locale): array
    {
        if (!in_array($locale, ['fa', 'en'], true)) {
            return [];
        }
        if (!isset(self::$cache[$locale])) {
            $file = BASE_PATH . '/lang/' . $locale . '.php';
            self::$cache[$locale] = is_file($file) ? (require $file) : [];
        }
        return self::$cache[$locale];
    }
}

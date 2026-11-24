<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class DateHelper
{
    public static function format(?string $datetime, bool $withTime = true): string
    {
        if (!$datetime) {
            return '-';
        }
        try {
            $dt = new DateTimeImmutable($datetime);
        } catch (\Throwable) {
            return $datetime;
        }

        $locale = Translator::locale();
        if ($locale === 'fa') {
            [$jy, $jm, $jd] = self::gregorianToJalali(
                (int) $dt->format('Y'),
                (int) $dt->format('n'),
                (int) $dt->format('j')
            );
            $dateStr = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
            if ($withTime) {
                $dateStr .= ' ' . $dt->format('H:i');
            }
            return self::localizeDigits($dateStr);
        }

        return $withTime ? $dt->format('Y-m-d H:i') : $dt->format('Y-m-d');
    }

    public static function localizeDigits(string|int|float $input): string
    {
        $str = (string) $input;
        if (Translator::locale() === 'fa') {
            return strtr($str, [
                '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
                '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
            ]);
        }
        return $str;
    }

    public static function number(int|float|string $value, ?int $decimals = null): string
    {
        if ($decimals !== null && is_numeric($value)) {
            $formatted = number_format((float) $value, $decimals, '.', '');
        } else {
            $formatted = (string) $value;
        }
        return self::localizeDigits($formatted);
    }

    public static function percent(int|float|string $value): string
    {
        return self::localizeDigits((string) $value) . (Translator::locale() === 'fa' ? '٪' : '%');
    }

    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + ((int) (($gy2 + 3) / 4)) - ((int) (($gy2 + 99) / 100))
            + ((int) (($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * ((int) ($days / 12053)));
        $days %= 12053;
        $jy += 4 * ((int) ($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int) ($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int) (($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return [$jy, $jm, $jd];
    }
}

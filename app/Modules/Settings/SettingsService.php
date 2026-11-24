<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Database;

final class SettingsService
{
    public static function all(): array
    {
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
        $map = [];
        foreach ($rows as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $val = Database::fetchColumn('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);
        return $val === false || $val === null ? $default : (string) $val;
    }

    public static function set(string $key, ?string $value, string $group = 'general'): void
    {
        $now = date('Y-m-d H:i:s');
        $exists = Database::fetch('SELECT setting_key FROM settings WHERE setting_key = ?', [$key]);
        if ($exists) {
            Database::execute(
                'UPDATE settings SET setting_value = ?, setting_group = ?, updated_at = ? WHERE setting_key = ?',
                [$value, $group, $now, $key]
            );
        } else {
            Database::execute(
                'INSERT INTO settings (setting_key, setting_value, setting_group, updated_at) VALUES (?, ?, ?, ?)',
                [$key, $value, $group, $now]
            );
        }
    }

    public static function setMany(array $pairs, string $group = 'general'): void
    {
        foreach ($pairs as $k => $v) {
            self::set((string) $k, $v !== null ? (string) $v : null, $group);
        }
    }
}

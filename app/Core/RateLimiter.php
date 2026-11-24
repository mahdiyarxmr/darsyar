<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    public static function isLocked(string $identifier, string $ip): bool
    {
        $max = (int) Config::get('app.login_rate_limit.max_attempts', 5);
        $window = (int) Config::get('app.login_rate_limit.window_seconds', 900);
        $cutoff = date('Y-m-d H:i:s', time() - $window);

        $count = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM login_attempts WHERE (identifier = ? OR ip_address = ?) AND success = 0 AND attempted_at >= ?',
            [mb_strtolower(trim($identifier)), $ip, $cutoff]
        );

        return $count >= $max;
    }

    public static function record(string $identifier, string $ip, bool $success): void
    {
        Database::execute(
            'INSERT INTO login_attempts (identifier, ip_address, attempted_at, success) VALUES (?, ?, ?, ?)',
            [mb_strtolower(trim($identifier)), $ip, date('Y-m-d H:i:s'), $success ? 1 : 0]
        );

        if ($success) {
            Database::execute(
                'DELETE FROM login_attempts WHERE identifier = ? AND success = 0',
                [mb_strtolower(trim($identifier))]
            );
        }
    }
}

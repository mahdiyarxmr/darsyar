<?php

declare(strict_types=1);

namespace App\Core;

final class Ui
{
    public static function breadcrumbs(array $items): string
    {
        if ($items === []) {
            return '';
        }
        $sep = Translator::isRtl() ? Icon::svg('chevron-left', 14) : Icon::svg('chevron-right', 14);
        $html = '<nav class="breadcrumbs" aria-label="Breadcrumb">';
        $last = count($items) - 1;
        foreach ($items as $idx => $item) {
            $label = View::e((string) ($item['label'] ?? ''));
            $url = $item['url'] ?? null;
            if ($idx > 0) {
                $html .= '<span class="breadcrumb-sep" aria-hidden="true">' . $sep . '</span>';
            }
            if ($url && $idx < $last) {
                $html .= '<a href="' . View::e(View::url((string) $url)) . '" class="breadcrumb-link">' . $label . '</a>';
            } else {
                $html .= '<span class="breadcrumb-current">' . $label . '</span>';
            }
        }
        $html .= '</nav>';
        return $html;
    }

    public static function emptyState(string $message, string $icon = 'folder-open', ?string $actionLabel = null, ?string $actionUrl = null): string
    {
        $html = '<div class="empty-state">';
        $html .= '<div class="empty-state-icon">' . Icon::svg($icon, 28) . '</div>';
        $html .= '<p class="empty-state-text">' . View::e($message) . '</p>';
        if ($actionLabel && $actionUrl) {
            $html .= '<a href="' . View::e(View::url($actionUrl)) . '" class="btn btn-primary btn-sm">' . View::e($actionLabel) . '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    public static function pagination(int $page, int $totalPages, string $baseUrl): string
    {
        if ($totalPages <= 1) {
            return '';
        }
        $sep = str_contains($baseUrl, '?') ? '&' : '?';
        $prevIcon = Translator::isRtl() ? Icon::svg('chevron-right', 16) : Icon::svg('chevron-left', 16);
        $nextIcon = Translator::isRtl() ? Icon::svg('chevron-left', 16) : Icon::svg('chevron-right', 16);

        $html = '<nav class="pagination" aria-label="Pagination">';
        if ($page > 1) {
            $html .= '<a class="page-link" href="' . View::e(View::url($baseUrl . $sep . 'page=' . ($page - 1))) . '" aria-label="' . View::e(Translator::get('common.prev')) . '">' . $prevIcon . '</a>';
        }
        for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
            $active = $i === $page ? ' active' : '';
            $html .= '<a class="page-link' . $active . '" href="' . View::e(View::url($baseUrl . $sep . 'page=' . $i)) . '">' . View::e(DateHelper::number($i)) . '</a>';
        }
        if ($page < $totalPages) {
            $html .= '<a class="page-link" href="' . View::e(View::url($baseUrl . $sep . 'page=' . ($page + 1))) . '" aria-label="' . View::e(Translator::get('common.next')) . '">' . $nextIcon . '</a>';
        }
        $html .= '</nav>';
        return $html;
    }

    public static function ratingLabel(?string $raw): string
    {
        return match ($raw) {
            'Excellent' => Translator::get('rating.excellent'),
            'Very Good' => Translator::get('rating.very_good'),
            'Good' => Translator::get('rating.good'),
            'Needs Improvement' => Translator::get('rating.needs_improvement'),
            default => (string) ($raw ?? '-'),
        };
    }

    public static function statusLabel(string $status): string
    {
        $key = 'status.' . strtolower($status);
        $val = Translator::get($key);
        return $val === $key ? $status : $val;
    }

    public static function weekdayLabel(string $day): string
    {
        $key = 'weekday.' . strtolower($day);
        $val = Translator::get($key);
        return $val === $key ? ucfirst($day) : $val;
    }
}

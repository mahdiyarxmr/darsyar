<?php

declare(strict_types=1);

namespace App\Core;

use finfo;
use RuntimeException;

final class Uploader
{
    public const ALLOWED_DOCS_AND_IMAGES = [
        'pdf'  => ['application/pdf'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
    ];

    public const ALLOWED_IMAGES_ONLY = [
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
    ];

    public static function upload(
        array $file,
        string $subDir,
        array $allowedMap = self::ALLOWED_DOCS_AND_IMAGES,
        ?int $maxBytes = null
    ): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(Translator::get('errors.upload_failed'));
        }

        $maxSize = $maxBytes ?? (int) Config::get('app.max_upload_bytes', 10485760);
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxSize) {
            throw new RuntimeException(Translator::get('errors.upload_too_large'));
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_file($tmpPath)) {
            throw new RuntimeException(Translator::get('errors.upload_failed'));
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!isset($allowedMap[$ext])) {
            throw new RuntimeException(Translator::get('errors.upload_invalid_type'));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpPath);
        if (!in_array($mime, $allowedMap[$ext], true)) {
            throw new RuntimeException(Translator::get('errors.upload_invalid_type'));
        }

        $safeSubDir = trim(preg_replace('/[^a-zA-Z0-9_\/-]/', '', $subDir), '/');
        $targetDir = STORAGE_PATH . '/uploads/' . $safeSubDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $filename;

        $moved = is_uploaded_file($tmpPath)
            ? move_uploaded_file($tmpPath, $targetPath)
            : copy($tmpPath, $targetPath);

        if (!$moved) {
            throw new RuntimeException(Translator::get('errors.upload_failed'));
        }

        return $safeSubDir . '/' . $filename;
    }

    public static function resolveStoragePath(string $relativePath): ?string
    {
        $clean = ltrim(str_replace(['..', '\\'], ['', '/'], $relativePath), '/');
        $full = STORAGE_PATH . '/uploads/' . $clean;
        $realBase = realpath(STORAGE_PATH . '/uploads');
        $realTarget = realpath($full);
        if ($realBase === false || $realTarget === false || !str_starts_with($realTarget, $realBase)) {
            return null;
        }
        return $realTarget;
    }
}

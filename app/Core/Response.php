<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private int $status = 200;
    private array $headers = [];
    private string $body = '';

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => View::url($url)]);
    }

    public static function csv(string $filename, array $headers, array $rows): self
    {
        $handle = fopen('php://temp', 'r+');
        // UTF-8 BOM so Excel renders Persian UTF-8 characters cleanly
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return new self($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename) . '"',
        ]);
    }

    public static function fileDownload(string $filePath, string $downloadName, string $mimeType = 'application/octet-stream'): self
    {
        $content = is_file($filePath) ? (file_get_contents($filePath) ?: '') : '';
        $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $downloadName);
        return new self($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $safeName . '"',
            'Content-Length' => (string) strlen($content),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}

<?php

declare(strict_types=1);

namespace App;

use Throwable;

class PdfGenerator
{
    public static function render(string $html, string $filename, bool $inline = true): void
    {
        if (!class_exists('Dompdf\\Dompdf')) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: text/html; charset=utf-8');
            ?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($filename) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .notice { background: #fff3cd; border: 1px solid #ffecb5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .actions { margin-bottom: 20px; }
        button { background: #0d6efd; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0b5ed7; }
    </style>
</head>
<body>
    <div class="notice">
        <strong>Note:</strong> PDF generation is not available because the <code>dompdf</code> library is not installed. You can still view and print the document below.
    </div>
    <div class="actions">
        <button onclick="window.print()">📄 Print Document</button>
    </div>
    <hr>
    <div class="document-content">
        <?= self::wrapHtml($html) ?>
    </div>
</body>
</html><?php
            exit;
        }
        
        try {
            $pdf = self::generate($html);
        } catch (Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            $msg = \app_is_debug()
                ? $e->getMessage()
                : 'PDF generation failed. Please try again.';
            echo $msg;
            exit;
        }

        self::sendPdf($pdf, $filename, $inline);
    }

    public static function generate(string $html): string
    {
        @ini_set('memory_limit', '256M');
        @set_time_limit(120);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', dirname(__DIR__));
        $options->set('tempDir', sys_get_temp_dir());

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(self::wrapHtml($html), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private static function wrapHtml(string $html): string
    {
        $html = trim($html);
        if (stripos($html, '<!DOCTYPE') !== false || stripos($html, '<html') !== false) {
            return $html;
        }
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Document</title></head><body>' . $html . '</body></html>';
    }

    private static function sendPdf(string $pdf, string $filename, bool $inline): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (headers_sent()) {
            exit('Cannot send PDF — output already started.');
        }

        $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'document.pdf';
        if (!str_ends_with(strtolower($safe), '.pdf')) {
            $safe .= '.pdf';
        }

        $disposition = $inline ? 'inline' : 'attachment';

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disposition . '; filename="' . $safe . '"');
        header('Content-Length: ' . (string) strlen($pdf));
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');

        echo $pdf;
        exit;
    }

    public static function logoHtml(?string $logoPath, string $companyName): string
    {
        if ($logoPath && is_file(dirname(__DIR__) . '/' . $logoPath)) {
            $abs = realpath(dirname(__DIR__) . '/' . $logoPath);
            if ($abs) {
                $mime = mime_content_type($abs) ?: 'image/png';
                $data = base64_encode((string) file_get_contents($abs));
                return "<img src='data:{$mime};base64,{$data}' alt='Logo' style='max-height:70px;max-width:200px'>";
            }
        }
        return '<div style="font-size:18px;font-weight:bold">' . htmlspecialchars($companyName) . '</div>';
    }
}

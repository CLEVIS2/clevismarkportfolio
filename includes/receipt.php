<?php

function clevis_receipt_number(array $request): string
{
    $requestId = (int)($request['id'] ?? 0);
    $createdAt = (string)($request['created_at'] ?? '');
    $datePart = $createdAt !== ''
        ? clevis_format_east_africa_datetime($createdAt, 'Ymd')
        : clevis_format_east_africa_datetime(null, 'Ymd');

    return 'RCPT-' . $datePart . '-' . str_pad((string)max(1, $requestId), 5, '0', STR_PAD_LEFT);
}

function clevis_receipt_date_label(array $request): string
{
    $createdAt = (string)($request['created_at'] ?? '');

    return clevis_format_east_africa_datetime($createdAt, 'M j, Y g:i a');
}

function clevis_receipt_public_base_url(): string
{
    $envBaseUrl = getenv('APP_BASE_URL');
    if (is_string($envBaseUrl) && trim($envBaseUrl) !== '') {
        return rtrim(trim($envBaseUrl), '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '/clevis/receipt_pdf.php');
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if ($host !== '') {
        return $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');
    }

    return 'http://localhost/clevis';
}

function clevis_receipt_print_url(string $requestCode): string
{
    return clevis_receipt_public_base_url() . '/receipt.php?request_code=' . urlencode($requestCode) . '&print=1';
}

function clevis_pdf_browser_candidates(): array
{
    $candidates = [];

    $envBrowser = getenv('CLEVIS_PDF_BROWSER');
    if (is_string($envBrowser) && trim($envBrowser) !== '') {
        $candidates[] = trim($envBrowser);
    }

    $candidates[] = 'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe';
    $candidates[] = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
    $candidates[] = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    $candidates[] = 'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe';

    return array_values(array_filter(array_unique($candidates)));
}

function clevis_generate_pdf_from_browser(string $url): ?string
{
    foreach (clevis_pdf_browser_candidates() as $browserPath) {
        if (!is_file($browserPath)) {
            continue;
        }

        $tempBase = tempnam(sys_get_temp_dir(), 'clevis_pdf_');
        if ($tempBase === false) {
            continue;
        }

        $pdfPath = $tempBase . '.pdf';
        @unlink($tempBase);

        $command = '"' . $browserPath . '" --headless --disable-gpu --no-first-run --print-to-pdf-no-header --print-to-pdf="' . $pdfPath . '" "' . $url . '"';
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            @unlink($pdfPath);
            continue;
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode === 0 && is_file($pdfPath) && filesize($pdfPath) > 0) {
            $pdf = file_get_contents($pdfPath);
            @unlink($pdfPath);

            if ($pdf !== false && $pdf !== '') {
                return $pdf;
            }
        }

        @unlink($pdfPath);

        if ($stdout === false && $stderr === false) {
            continue;
        }
    }

    return null;
}

function clevis_pdf_escape_text(string $text): string
{
    return str_replace(["\\", "(", ")", "\r\n", "\r"], ["\\\\", "\\(", "\\)", "\n", "\n"], $text);
}

function clevis_pdf_wrap_text(string $text, int $limit = 64): array
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

    if ($text === '') {
        return [''];
    }

    return explode("\n", wordwrap($text, $limit, "\n", true));
}

function clevis_pdf_color(string $command, float $r, float $g, float $b): string
{
    return sprintf("%.3F %.3F %.3F %s\n", $r, $g, $b, $command);
}

function clevis_pdf_top_to_bottom(float $top, float $pageHeight = 842.0): float
{
    return $pageHeight - $top;
}

function clevis_pdf_text(float $x, float $top, string $text, string $font, float $size, float $r = 0.25, float $g = 0.28, float $b = 0.34): string
{
    $y = clevis_pdf_top_to_bottom($top);

    return sprintf(
        "q\n%.3F %.3F %.3F rg\nBT\n/%s %.2F Tf\n1 0 0 1 %.2F %.2F Tm\n(%s) Tj\nET\nQ\n",
        $r,
        $g,
        $b,
        $font,
        $size,
        $x,
        $y,
        clevis_pdf_escape_text($text)
    );
}

function clevis_pdf_multiline_text(float $x, float $top, array $lines, string $font, float $size, float $leading = 14.0, float $r = 0.25, float $g = 0.28, float $b = 0.34): string
{
    $stream = '';

    foreach ($lines as $index => $line) {
        $stream .= clevis_pdf_text($x, $top + ($index * $leading), (string)$line, $font, $size, $r, $g, $b);
    }

    return $stream;
}

function clevis_pdf_rect(float $x, float $top, float $w, float $h, array $fill = null, array $stroke = null, float $lineWidth = 1.0): string
{
    $y = clevis_pdf_top_to_bottom($top + $h);
    $stream = "q\n";

    if ($stroke !== null) {
        $stream .= sprintf("%.3F %.3F %.3F RG\n", $stroke[0], $stroke[1], $stroke[2]);
    }

    if ($fill !== null) {
        $stream .= sprintf("%.3F %.3F %.3F rg\n", $fill[0], $fill[1], $fill[2]);
    }

    $stream .= sprintf("%.2F w\n%.2F %.2F %.2F %.2F re\n", $lineWidth, $x, $y, $w, $h);

    if ($fill !== null && $stroke !== null) {
        $stream .= "B\n";
    } elseif ($fill !== null) {
        $stream .= "f\n";
    } elseif ($stroke !== null) {
        $stream .= "S\n";
    } else {
        $stream .= "n\n";
    }

    $stream .= "Q\n";

    return $stream;
}

function clevis_pdf_circle(float $cx, float $topCenter, float $r, array $fill = null, array $stroke = null, float $lineWidth = 1.0): string
{
    $cy = clevis_pdf_top_to_bottom($topCenter);
    $c = 0.552284749831;
    $ox = $r * $c;
    $oy = $r * $c;
    $x0 = $cx - $r;
    $x1 = $cx + $r;
    $y0 = $cy - $r;
    $y1 = $cy + $r;

    $stream = "q\n";

    if ($stroke !== null) {
        $stream .= sprintf("%.3F %.3F %.3F RG\n", $stroke[0], $stroke[1], $stroke[2]);
    }

    if ($fill !== null) {
        $stream .= sprintf("%.3F %.3F %.3F rg\n", $fill[0], $fill[1], $fill[2]);
    }

    $stream .= sprintf("%.2F w\n", $lineWidth);
    $stream .= sprintf("%.2F %.2F m\n", $cx, $y1);
    $stream .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $cx + $ox, $y1, $x1, $cy + $oy, $x1, $cy);
    $stream .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $x1, $cy - $oy, $cx + $ox, $y0, $cx, $y0);
    $stream .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $cx - $ox, $y0, $x0, $cy - $oy, $x0, $cy);
    $stream .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $x0, $cy + $oy, $cx - $ox, $y1, $cx, $y1);

    if ($fill !== null && $stroke !== null) {
        $stream .= "B\n";
    } elseif ($fill !== null) {
        $stream .= "f\n";
    } elseif ($stroke !== null) {
        $stream .= "S\n";
    } else {
        $stream .= "n\n";
    }

    $stream .= "Q\n";

    return $stream;
}

function clevis_pdf_line(float $x1, float $top1, float $x2, float $top2, array $stroke, float $lineWidth = 1.0): string
{
    $y1 = clevis_pdf_top_to_bottom($top1);
    $y2 = clevis_pdf_top_to_bottom($top2);

    return sprintf(
        "q\n%.3F %.3F %.3F RG\n%.2F w\n%.2F %.2F m\n%.2F %.2F l\nS\nQ\n",
        $stroke[0],
        $stroke[1],
        $stroke[2],
        $lineWidth,
        $x1,
        $y1,
        $x2,
        $y2
    );
}

function clevis_pdf_content_stream(array $request, string $receiptNumber, string $issuedAt): string
{
    $clientName = (string)($request['client_name'] ?? 'N/A');
    $requestCode = (string)($request['request_code'] ?? 'N/A');
    $status = (string)($request['request_status'] ?? 'New');
    $serviceType = (string)($request['service_type'] ?? 'N/A');
    $budgetRange = (string)(($request['budget_range'] ?? '') !== '' ? $request['budget_range'] : 'N/A');
    $phone = (string)(($request['phone'] ?? '') !== '' ? $request['phone'] : 'N/A');
    $email = (string)($request['email'] ?? 'N/A');
    $preferredContact = (string)($request['preferred_contact'] ?? 'N/A');
    $submittedAt = clevis_receipt_date_label($request);
    $details = clevis_pdf_wrap_text((string)($request['project_details'] ?? ''), 55);

    $stream = '';
    $stream .= clevis_pdf_rect(24, 24, 547, 794, [0.995, 0.995, 0.995], [0.88, 0.88, 0.88], 1.0);

    $stream .= clevis_pdf_circle(73, 80, 31, [0.18, 0.58, 0.52], [0.18, 0.58, 0.52], 1.0);
    $stream .= clevis_pdf_text(56, 68, 'CM', 'F4', 22, 1.0, 1.0, 1.0);
    $stream .= clevis_pdf_text(112, 70, 'Clevis Mark', 'F4', 31, 0.26, 0.29, 0.36);
    $stream .= clevis_pdf_text(115, 102, 'Web Developer', 'F1', 13, 0.48, 0.52, 0.61);

    $stream .= clevis_pdf_text(392, 74, 'Receipt No.', 'F1', 11, 0.55, 0.60, 0.68);
    $stream .= clevis_pdf_text(392, 92, $receiptNumber, 'F4', 17, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(471, 74, 'Client No.', 'F1', 11, 0.55, 0.60, 0.68);
    $stream .= clevis_pdf_text(471, 92, $clientName, 'F4', 14, 0.25, 0.28, 0.34);

    $stream .= clevis_pdf_rect(24, 146, 547, 62, [0.18, 0.58, 0.52], [0.18, 0.58, 0.52], 1.0);
    $stream .= clevis_pdf_circle(65, 177, 18, [0.83, 0.94, 0.91], [0.83, 0.94, 0.91], 1.0);
    $stream .= clevis_pdf_text(56, 182, 'V', 'F4', 24, 0.18, 0.58, 0.52);
    $stream .= clevis_pdf_text(99, 176, 'REQUEST RECEIVED SUCCESSFULLY', 'F4', 22, 1.0, 1.0, 1.0);

    $stream .= clevis_pdf_rect(24, 222, 547, 78, [0.986, 0.986, 0.986], [0.91, 0.91, 0.91], 0.8);
    $stream .= clevis_pdf_text(50, 247, 'Receipt No.', 'F1', 12, 0.57, 0.61, 0.68);
    $stream .= clevis_pdf_text(138, 247, $receiptNumber, 'F4', 15, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(370, 247, 'Date.', 'F1', 12, 0.57, 0.61, 0.68);
    $stream .= clevis_pdf_text(420, 247, $issuedAt, 'F1', 12, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 279, 'Request Code.', 'F1', 12, 0.57, 0.61, 0.68);
    $stream .= clevis_pdf_text(153, 279, $requestCode, 'F1', 12, 0.25, 0.28, 0.34);

    $stream .= clevis_pdf_line(350, 235, 350, 285, [0.88, 0.88, 0.88], 1.0);

    $stream .= clevis_pdf_text(50, 348, 'Client Details', 'F1', 19, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 393, $clientName, 'F4', 20, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 430, $email, 'F1', 12, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 468, $phone, 'F1', 12, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 505, 'Preferred Contact: ' . $preferredContact, 'F1', 11, 0.28, 0.32, 0.38);
    $stream .= clevis_pdf_text(50, 525, 'Submitted: ' . $submittedAt, 'F1', 11, 0.28, 0.32, 0.38);

    $stream .= clevis_pdf_line(290, 340, 290, 534, [0.90, 0.90, 0.90], 1.0);
    $stream .= clevis_pdf_text(310, 348, 'Your Business', 'F1', 19, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(310, 393, 'Clevis Mark', 'F4', 20, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(310, 430, 'Web Developer', 'F1', 12, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(310, 468, 'Phone: 0743483176', 'F1', 12, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(310, 506, 'Kiambu Road, Northern Bypass', 'F1', 12, 0.25, 0.28, 0.34);

    $stream .= clevis_pdf_line(24, 546, 571, 546, [0.90, 0.90, 0.90], 1.0);
    $stream .= clevis_pdf_text(50, 580, 'SERVICE DETAILS', 'F1', 14, 0.50, 0.55, 0.62);
    $stream .= clevis_pdf_text(50, 628, 'SERVICE:', 'F1', 13, 0.35, 0.40, 0.48);
    $stream .= clevis_pdf_text(128, 628, $serviceType, 'F4', 21, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 670, 'Budget:', 'F1', 12, 0.52, 0.57, 0.64);
    $stream .= clevis_pdf_text(128, 670, 'KES ' . $budgetRange, 'F1', 13, 0.25, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 710, 'Status', 'F1', 12, 0.52, 0.57, 0.64);
    $stream .= clevis_pdf_text(128, 710, clevis_normalize_request_status($status), 'F1', 13, 0.25, 0.28, 0.34);

    $stream .= clevis_pdf_rect(380, 575, 165, 205, [1.0, 1.0, 1.0], [0.85, 0.87, 0.91], 1.0);
    $stream .= clevis_pdf_rect(402, 600, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(487, 600, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(402, 685, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(444, 600, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(466, 630, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(420, 660, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(470, 680, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(504, 660, 18, 18, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_rect(498, 630, 12, 12, [0.23, 0.26, 0.33], [0.23, 0.26, 0.33], 0.5);
    $stream .= clevis_pdf_text(394, 802, 'Request Code', 'F1', 9, 0.53, 0.58, 0.66);
    $stream .= clevis_pdf_text(394, 822, $requestCode, 'F4', 11, 0.25, 0.28, 0.34);

    $stream .= clevis_pdf_rect(50, 742, 360, 32, [0.35, 0.52, 0.58], [0.35, 0.52, 0.58], 1.0);
    $stream .= clevis_pdf_text(65, 763, 'Request Code:', 'F1', 11, 1.0, 1.0, 1.0);
    $stream .= clevis_pdf_text(158, 763, $requestCode, 'F4', 12, 1.0, 1.0, 1.0);

    $stream .= clevis_pdf_text(50, 780, 'info@clevismark.com', 'F1', 15, 0.45, 0.50, 0.58);
    $stream .= clevis_pdf_text(50, 806, 'Thank you for choosing us.', 'F1', 12, 0.24, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 824, "We're here if you need any assistance.", 'F1', 12, 0.24, 0.28, 0.34);
    $stream .= clevis_pdf_text(50, 834, 'Keep this receipt and request code safe for checking feedback later.', 'F1', 9, 0.58, 0.62, 0.68);

    return $stream;
}

function clevis_pdf_stream(array $pages): string
{
    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = null;
    $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
    $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman >>";
    $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold >>";

    $pageObjectNumbers = [];
    $contentObjectNumbers = [];
    $nextObjectNumber = 7;

    foreach ($pages as $pageContent) {
        $pageObjectNumbers[] = $nextObjectNumber++;
        $contentObjectNumbers[] = $nextObjectNumber++;
    }

    $kids = [];
    foreach ($pageObjectNumbers as $pageObjectNumber) {
        $kids[] = $pageObjectNumber . ' 0 R';
    }

    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pageObjectNumbers) . ' >>';

    foreach ($pages as $index => $pageContent) {
        $pageObjectNumber = $pageObjectNumbers[$index];
        $contentObjectNumber = $contentObjectNumbers[$index];

        $objects[$contentObjectNumber] = '<< /Length ' . strlen($pageContent) . " >>\nstream\n" . $pageContent . "\nendstream";
        $objects[$pageObjectNumber] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R /F4 6 0 R >> >> /Contents ' . $contentObjectNumber . ' 0 R >>';
    }

    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0 => 0];

    foreach ($objects as $number => $object) {
        $offsets[$number] = strlen($pdf);
        $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefStart = strlen($pdf);
    $maxObjectNumber = max(array_keys($objects));
    $pdf .= "xref\n";
    $pdf .= "0 " . ($maxObjectNumber + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= $maxObjectNumber; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    }

    $pdf .= "trailer\n";
    $pdf .= "<< /Size " . ($maxObjectNumber + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n";
    $pdf .= $xrefStart . "\n";
    $pdf .= "%%EOF";

    return $pdf;
}

function clevis_build_receipt_pdf(array $request, array $feedbackMessages): string
{
    $requestCode = (string)($request['request_code'] ?? '');

    if ($requestCode !== '') {
        $browserPdf = clevis_generate_pdf_from_browser(clevis_receipt_print_url($requestCode));
        if (is_string($browserPdf) && $browserPdf !== '') {
            return $browserPdf;
        }
    }

    $receiptNumber = clevis_receipt_number($request);
    $issuedAt = clevis_receipt_date_label($request);
    $pages = [clevis_pdf_content_stream($request, $receiptNumber, $issuedAt)];

    return clevis_pdf_stream($pages);
}

function clevis_send_mail_message(string $to, string $subject, string $body, ?string $attachmentBytes = null, ?string $attachmentName = null): bool
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
        return false;
    }

    $fromEmail = getenv('MAIL_FROM_EMAIL') ?: 'no-reply@clevis.local';
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Clevis Mark';

    if ($attachmentBytes === null) {
        $headers = [
            'MIME-Version: 1.0',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }

    $boundary = '=_clevis_' . md5((string)microtime(true) . $to . $subject);
    $safeAttachmentName = $attachmentName ?: 'attachment.pdf';

    $message = [];
    $message[] = 'This is a multi-part message in MIME format.';
    $message[] = '--' . $boundary;
    $message[] = 'Content-Type: text/plain; charset=UTF-8';
    $message[] = 'Content-Transfer-Encoding: 8bit';
    $message[] = '';
    $message[] = $body;
    $message[] = '--' . $boundary;
    $message[] = 'Content-Type: application/pdf; name="' . $safeAttachmentName . '"';
    $message[] = 'Content-Transfer-Encoding: base64';
    $message[] = 'Content-Disposition: attachment; filename="' . $safeAttachmentName . '"';
    $message[] = '';
    $message[] = chunk_split(base64_encode($attachmentBytes));
    $message[] = '--' . $boundary . '--';

    $headers = [
        'MIME-Version: 1.0',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
    ];

    return @mail($to, $subject, implode("\r\n", $message), implode("\r\n", $headers));
}

function clevis_send_receipt_email(array $request, array $feedbackMessages): bool
{
    $to = trim((string)($request['email'] ?? ''));
    $receiptNumber = clevis_receipt_number($request);
    $requestCode = (string)($request['request_code'] ?? 'N/A');
    $clientName = (string)($request['client_name'] ?? 'Client');
    $pdf = clevis_build_receipt_pdf($request, $feedbackMessages);

    $body = implode("\n", [
        'Hello ' . $clientName . ',',
        '',
        'Thank you for submitting your request.',
        'Receipt Number: ' . $receiptNumber,
        'Request Code: ' . $requestCode,
        'Status: ' . clevis_normalize_request_status((string)($request['request_status'] ?? 'New')),
        '',
        'Your receipt PDF is attached to this email.',
        'You can use the request code later to view feedback on the website.',
    ]);

    return clevis_send_mail_message($to, 'Your receipt from Clevis Mark', $body, $pdf, $receiptNumber . '.pdf');
}

function clevis_send_request_status_email(array $request, string $oldStatus, string $newStatus): bool
{
    $to = trim((string)($request['email'] ?? ''));
    $clientName = (string)($request['client_name'] ?? 'Client');
    $requestCode = (string)($request['request_code'] ?? 'N/A');

    $body = implode("\n", [
        'Hello ' . $clientName . ',',
        '',
        'Your request status has been updated.',
        'Request Code: ' . $requestCode,
        'Previous Status: ' . $oldStatus,
        'New Status: ' . $newStatus,
        '',
        'You can return to the website and check for updates using your request code.',
    ]);

    return clevis_send_mail_message($to, 'Request status updated - ' . $requestCode, $body);
}

function clevis_send_request_feedback_email(array $request, string $message): bool
{
    $to = trim((string)($request['email'] ?? ''));
    $clientName = (string)($request['client_name'] ?? 'Client');
    $requestCode = (string)($request['request_code'] ?? 'N/A');
    $status = clevis_normalize_request_status((string)($request['request_status'] ?? 'New'));

    $body = implode("\n", [
        'Hello ' . $clientName . ',',
        '',
        'A new feedback message has been posted for your request.',
        'Request Code: ' . $requestCode,
        'Current Status: ' . $status,
        '',
        'Message:',
        $message,
        '',
        'Please sign in or return to the receipt page to view more details.',
    ]);

    return clevis_send_mail_message($to, 'New feedback for ' . $requestCode, $body);
}

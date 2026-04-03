<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/receipt.php';

$requestCode = clevis_normalize_request_code($_GET['request_code'] ?? $_SESSION['last_request_code'] ?? '');

if ($requestCode === '' || !$pdo) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Receipt not found.';
    exit;
}

$request = clevis_get_request_by_code($pdo, $requestCode);

if (!$request) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Receipt not found.';
    exit;
}

$pdf = clevis_build_receipt_pdf($request, []);
$safeFileName = preg_replace('/[^A-Z0-9-]/', '', strtoupper($requestCode)) ?: 'receipt';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $safeFileName . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;

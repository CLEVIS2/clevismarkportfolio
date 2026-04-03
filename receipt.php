<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/receipt.php';

$notice = $_SESSION['receipt_notice'] ?? null;
unset($_SESSION['receipt_notice']);

$requestCode = clevis_normalize_request_code($_GET['request_code'] ?? $_SESSION['last_request_code'] ?? '');
$printMode = isset($_GET['print']) && $_GET['print'] !== '0';
$request = null;
$feedbackMessages = [];
$receiptNumber = null;
$issuedAt = null;
$uploadedFiles = [
    'brief' => [],
    'logo' => [],
    'references' => [],
];

if ($requestCode !== '' && $pdo) {
    $request = clevis_get_request_by_code($pdo, $requestCode);

    if ($request) {
        $feedbackMessages = clevis_get_feedback_by_request_code($pdo, $requestCode);
        $receiptNumber = clevis_receipt_number($request);
        $issuedAt = clevis_receipt_date_label($request);
        $uploadedFiles = clevis_request_uploaded_files($request);
    }
}

$submittedAt = $request ? clevis_format_east_africa_datetime($request['created_at']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt | Clevis Mark</title>
    <meta name="description" content="Printable receipt for a submitted service request.">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="receipt-page<?= $printMode ? ' receipt-print-mode' : ''; ?>">
    <div class="receipt-scene">
        <main class="receipt-stage">
            <section class="receipt-sheet">
                <div class="receipt-sheet-frame">
                    <?php if ($request): ?>
                        <header class="receipt-top">
                            <div class="receipt-brand-lockup">
                                <div class="receipt-brand-badge" aria-hidden="true">CM</div>
                                <div class="receipt-brand-text">
                                    <h1>Clevis Mark</h1>
                                    <p>Web Developer</p>
                                </div>
                            </div>

                            <div class="receipt-top-meta">
                                <div>
                                    <span>Receipt No.</span>
                                    <strong><?= htmlspecialchars($receiptNumber ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div>
                                    <span>Client No.</span>
                                    <strong><?= htmlspecialchars($request['client_name'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                            </div>
                        </header>

                        <section class="receipt-banner">
                            <div class="receipt-banner-icon" aria-hidden="true">
                                <span>✓</span>
                            </div>
                            <h2>REQUEST RECEIVED SUCCESSFULLY</h2>
                        </section>

                        <section class="receipt-strip">
                            <div>
                                <span>Receipt No.</span>
                                <strong><?= htmlspecialchars($receiptNumber ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span>Date.</span>
                                <strong><?= htmlspecialchars($issuedAt ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span>Request Code.</span>
                                <strong><?= htmlspecialchars($requestCode !== '' ? $requestCode : 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                        </section>

                        <section class="receipt-columns">
                            <article class="receipt-column">
                                <h3>Client Details</h3>
                                <p class="receipt-name"><?= htmlspecialchars($request['client_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="receipt-line-item"><span aria-hidden="true">✉</span><?= htmlspecialchars($request['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="receipt-line-item"><span aria-hidden="true">☎</span><?= htmlspecialchars($request['phone'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="receipt-meta-line">Preferred Contact: <?= htmlspecialchars($request['preferred_contact'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="receipt-meta-line">Submitted: <?= htmlspecialchars($submittedAt ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>

                            <article class="receipt-column receipt-column-divider">
                                <h3>Your Business</h3>
                                <p class="receipt-name">Clevis Mark</p>
                                <p class="receipt-line-item">Web Developer</p>
                                <p class="receipt-line-item">Phone: 0743483176</p>
                                <p class="receipt-line-item"><span aria-hidden="true">⌖</span>Kiambu Road, Northern Bypass</p>
                            </article>
                        </section>

                        <section class="receipt-service">
                            <p class="receipt-service-label">Service Details</p>
                            <div class="receipt-service-grid">
                            <div class="receipt-service-copy">
                                <p class="receipt-service-row"><span>SERVICE:</span> <strong><?= htmlspecialchars($request['service_type'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                <p class="receipt-service-row"><span>Budget.</span> <strong><?= htmlspecialchars($request['budget_range'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                <p class="receipt-service-row"><span>Status</span> <strong><?= htmlspecialchars($request['request_status'] ?? 'New', ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            </div>

                                <aside class="receipt-qr-card">
                                    <div class="receipt-qr" aria-hidden="true">
                                        <span></span><span></span><span></span>
                                        <span></span><span></span><span></span>
                                        <span></span><span></span><span></span>
                                    </div>
                                    <p class="receipt-qr-caption">Request Code</p>
                                    <strong><?= htmlspecialchars($requestCode !== '' ? $requestCode : 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                                </aside>
                            </div>
                        </section>

                        <?php if ($uploadedFiles['brief'] !== [] || $uploadedFiles['logo'] !== [] || $uploadedFiles['references'] !== []): ?>
                            <section class="receipt-attachments">
                                <h3>Attachments</h3>
                                <div class="receipt-attachment-list">
                                    <?php if ($uploadedFiles['brief'] !== []): ?>
                                        <div class="receipt-attachment-group">
                                            <span>Brief</span>
                                            <?php foreach ($uploadedFiles['brief'] as $file): ?>
                                                <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                    <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($uploadedFiles['logo'] !== []): ?>
                                        <div class="receipt-attachment-group">
                                            <span>Logo</span>
                                            <?php foreach ($uploadedFiles['logo'] as $file): ?>
                                                <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                    <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($uploadedFiles['references'] !== []): ?>
                                        <div class="receipt-attachment-group">
                                            <span>References</span>
                                            <?php foreach ($uploadedFiles['references'] as $file): ?>
                                                <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                    <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <section class="receipt-footer-block">
                            <div class="receipt-request-code">
                                <span>Request Code:</span>
                                <strong><?= htmlspecialchars($requestCode !== '' ? $requestCode : 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>

                            <div class="receipt-closer">
                                <p>Thank you for choosing us.</p>
                                <p>We&apos;re here if you need any assistance.</p>
                                <p class="receipt-email">info@clevismark.com</p>
                                <p class="receipt-note">Keep this receipt and request code safe for checking feedback later.</p>
                            </div>
                        </section>

                        <div class="receipt-actions<?= $printMode ? ' is-hidden-print' : ''; ?>">
                            <button class="button primary" type="button" onclick="window.print()">Print Receipt</button>
                            <a class="button secondary" href="receipt_pdf.php?request_code=<?= urlencode($requestCode); ?>">Download PDF</a>
                            <a class="button secondary" href="index.php#request">Back to Request Form</a>
                            <a class="button secondary" href="index.php?request_code=<?= urlencode($requestCode); ?>#feedback">Check Feedback</a>
                        </div>
                    <?php else: ?>
                        <div class="flash error">
                            The receipt could not be loaded. Please check your request code or submit a new request.
                        </div>

                        <div class="receipt-actions<?= $printMode ? ' is-hidden-print' : ''; ?>">
                            <a class="button primary" href="index.php#request">Go Back</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <?php if ($printMode): ?>
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    <?php endif; ?>
</body>
</html>

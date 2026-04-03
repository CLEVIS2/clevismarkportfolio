<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$requestCode = clevis_normalize_request_code($_GET['request_code'] ?? '');
$verificationRequest = null;
$verificationError = null;
$uploadedFiles = [
    'brief' => [],
    'logo' => [],
    'references' => [],
];

if ($requestCode !== '') {
    if (!$pdo) {
        $verificationError = $database_error ?? 'Database connection failed.';
    } else {
        $verificationRequest = clevis_get_request_by_verification_code($pdo, $requestCode);

        if ($verificationRequest) {
            $uploadedFiles = clevis_request_uploaded_files($verificationRequest);
        } else {
            $verificationError = 'No active receipt was found for that code.';
        }
    }
}

$issuedAt = $verificationRequest ? clevis_format_east_africa_datetime($verificationRequest['created_at']) : null;
$receiptNumber = $verificationRequest ? clevis_receipt_number($verificationRequest) : null;
$feedbackMessages = [];
$matchType = 'unknown';

if ($verificationRequest && $pdo) {
    $feedbackMessages = clevis_get_feedback_by_request_code($pdo, $requestCode);
    $matchType = clevis_get_verification_match_type($pdo, $requestCode);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Receipt | Clevis Mark</title>
    <meta name="description" content="Public receipt verification page for Clevis Mark service requests.">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="verify-page">
    <div class="page-shell">
        <header class="topbar">
            <a class="brand" href="index.php">
                <span class="brand-mark" aria-hidden="true">CM</span>
                <span class="brand-name">Clevis Mark</span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-nav">
                <span class="menu-toggle-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>
            <button class="nav-overlay" type="button" aria-label="Close navigation" tabindex="-1"></button>
            <nav class="topnav" id="site-nav">
                <a class="nav-request" href="index.php#request">Request Service</a>
                <a class="nav-feedback" href="index.php#feedback">Feedback</a>
                <a class="nav-verify" href="verify.php">Verify Receipt</a>
                <a class="nav-pdf" href="portfolio_pdf.php">PDF Portfolio</a>
            </nav>
        </header>

        <main>
            <section class="section">
                <div class="section-heading">
                    <p class="eyebrow">Receipt Verification</p>
                    <h1>Check whether a receipt code is valid and review the saved request details.</h1>
                    <p class="hero-text">
                        Enter a request code to confirm the receipt, current status, and basic request details. This page is public, so it is ideal when a client forwards their receipt.
                    </p>
                </div>

                <form class="feedback-lookup-form verify-form" action="verify.php" method="get">
                    <label>
                        Receipt or Request Code
                        <input type="text" name="request_code" placeholder="Example: JOHNDOE-ABC123 or RCPT-20260327-00008" value="<?= htmlspecialchars($requestCode, ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                    <button class="button primary" type="submit">Verify Receipt</button>
                </form>

                <?php if ($verificationError): ?>
                    <div class="flash error">
                        <?= htmlspecialchars($verificationError, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php elseif ($verificationRequest): ?>
                    <?php
                        $requestStatus = clevis_normalize_request_status((string)($verificationRequest['request_status'] ?? 'New'));
                        $requestStatusSlug = strtolower(str_replace(' ', '-', $requestStatus));
                    ?>
                    <article class="verify-result">
                        <div class="verify-result-head">
                            <div>
                                <p class="eyebrow">Verified Receipt</p>
                                <h2><?= htmlspecialchars($verificationRequest['client_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="verify-match-note">
                                    <?php if ($matchType === 'receipt_number'): ?>
                                        Matched by receipt number
                                    <?php elseif ($matchType === 'request_code'): ?>
                                        Matched by request code
                                    <?php else: ?>
                                        Matched by receipt data
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="status-pill status-<?= htmlspecialchars($requestStatusSlug, ENT_QUOTES, 'UTF-8'); ?>">
                                <?= htmlspecialchars($requestStatus, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>

                        <div class="verify-grid">
                            <div>
                                <p><strong>Receipt No.</strong> <?= htmlspecialchars($receiptNumber ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Request Code.</strong> <?= htmlspecialchars($requestCode, ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Issued.</strong> <?= htmlspecialchars($issuedAt ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Service.</strong> <?= htmlspecialchars($verificationRequest['service_type'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Budget.</strong> <?= htmlspecialchars($verificationRequest['budget_range'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Preferred Contact.</strong> <?= htmlspecialchars($verificationRequest['preferred_contact'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>

                            <div>
                                <p><strong>Email.</strong> <?= htmlspecialchars($verificationRequest['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Phone.</strong> <?= htmlspecialchars($verificationRequest['phone'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Submitted.</strong> <?= htmlspecialchars($issuedAt ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Project Details.</strong> <?= nl2br(htmlspecialchars($verificationRequest['project_details'], ENT_QUOTES, 'UTF-8')); ?></p>
                            </div>
                        </div>

                        <?php if ($uploadedFiles['brief'] !== [] || $uploadedFiles['logo'] !== [] || $uploadedFiles['references'] !== []): ?>
                            <section class="receipt-attachments verify-attachments">
                                <h3>Uploaded Files</h3>
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

                        <section class="verify-feedback">
                            <h3>Feedback History</h3>
                            <?php if ($feedbackMessages !== []): ?>
                                <div class="feedback-list">
                                    <?php foreach ($feedbackMessages as $item): ?>
                                        <article class="feedback-card">
                                            <div class="feedback-card-head">
                                                <strong><?= htmlspecialchars($item['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <span><?= htmlspecialchars(clevis_format_east_africa_datetime($item['created_at']), ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                            <p><?= nl2br(htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8')); ?></p>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="admin-muted">No feedback has been posted for this request yet.</p>
                            <?php endif; ?>
                        </section>

                        <div class="verify-actions">
                            <a class="button primary" href="receipt.php?request_code=<?= urlencode($requestCode); ?>">Open Receipt</a>
                            <a class="button secondary" href="receipt_pdf.php?request_code=<?= urlencode($requestCode); ?>">Download PDF</a>
                        </div>
                    </article>
                <?php endif; ?>
                </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
